#!/bin/bash
# ============================================================
# VPN Install Script - Ubuntu 24.04
# Includes: SSH, Xray, Panel Simpel (port 1313)
# Repo: https://github.com/fahrialimudin/xray-with-panel-simpel
# ============================================================
export DEBIAN_FRONTEND=noninteractive
export NEEDRESTART_MODE=a
export NEEDRESTART_SUSPEND=1
if [ -f /etc/needrestart/needrestart.conf ]; then
    sed -i "s/#$nrconf{restart} = 'i';/$nrconf{restart} = 'a';/" /etc/needrestart/needrestart.conf
    sed -i "s/#$nrconf{kernelhints} = -1;/$nrconf{kernelhints} = -1;/" /etc/needrestart/needrestart.conf
fi
clear
rm -rf /etc/xray/domain /etc/v2ray/domain /etc/xray/scdomain /etc/v2ray/scdomain /var/lib/ipvps.conf

red='\e[1;31m'
green='\e[0;32m'
yell='\e[1;33m'
tyblue='\e[1;36m'
BRed='\e[1;31m'
BGreen='\e[1;32m'
BYellow='\e[1;33m'
BBlue='\e[1;34m'
NC='\e[0m'

REPO="https://raw.githubusercontent.com/fahrialimudin/xray-with-panel-simpel/main"
CDN="${REPO}/ssh"

cd /root

[ "${EUID}" -ne 0 ] && echo "You need to run this script as root" && exit 1
[ "$(systemd-detect-virt)" == "openvz" ] && echo "OpenVZ is not supported" && exit 1

localip=$(hostname -I | cut -d\  -f1)
hst=( `hostname` )
dart=$(cat /etc/hosts | grep -w `hostname` | awk '{print $2}')
if [[ "$hst" != "$dart" ]]; then
    echo "$localip $(hostname)" >> /etc/hosts
fi

mkdir -p /etc/xray /etc/v2ray
touch /etc/xray/domain /etc/v2ray/domain /etc/xray/scdomain /etc/v2ray/scdomain

echo -e "[ ${BBlue}NOTES${NC} ] Before we go.. "
sleep 0.5
echo -e "[ ${BBlue}NOTES${NC} ] I need check your headers first.."
sleep 0.5
echo -e "[ ${BGreen}INFO${NC} ] Checking headers"
sleep 0.5
totet=`uname -r`
REQUIRED_PKG="linux-headers-$totet"
PKG_OK=$(dpkg-query -W --showformat='${Status}\n' $REQUIRED_PKG 2>/dev/null|grep "install ok installed")
echo Checking for $REQUIRED_PKG: $PKG_OK
if [ "" = "$PKG_OK" ]; then
    echo -e "[ ${BRed}WARNING${NC} ] Try to install ...."
    apt-get --yes -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" install $REQUIRED_PKG >/dev/null 2>&1 || true
fi
clear

secs_to_human() {
    echo "Installation time : $(( ${1} / 3600 )) hours $(( (${1} / 60) % 60 )) minute's $(( ${1} % 60 )) seconds"
}
start=$(date +%s)
ln -fs /usr/share/zoneinfo/Asia/Jakarta /etc/localtime
sysctl -w net.ipv6.conf.all.disable_ipv6=1 >/dev/null 2>&1
sysctl -w net.ipv6.conf.default.disable_ipv6=1 >/dev/null 2>&1

echo -e "[ ${BGreen}INFO${NC} ] Preparing the install file"
apt install git curl -y >/dev/null 2>&1
apt install python3 python3-pip python3-is-python -y >/dev/null 2>&1
echo -e "[ ${BGreen}INFO${NC} ] Aight good ... installation file is ready"
sleep 0.5
echo -e "[ ${BGreen}INFO${NC} ] Check permission : "
echo -e "$BGreen Permission Accepted!$NC"
sleep 2

mkdir -p /var/lib/ >/dev/null 2>&1
echo "IP=" >> /var/lib/ipvps.conf

echo ""
clear
    echo -e "$BBlue                     SETUP DOMAIN VPS     $NC"
    echo -e "$BYellow----------------------------------------------------------$NC"
    echo -e "$BGreen 1. Use Domain Random / Gunakan Domain Random $NC"
    echo -e "$BGreen 2. Choose Your Own Domain / Gunakan Domain Sendiri $NC"
    echo -e "$BYellow----------------------------------------------------------$NC"
    read -rp " Pilih domain yang akan kamu pakai : " dns
    dns="${dns//[[:space:]]/}"
    if [[ "$dns" == "1" ]]; then
        clear
        apt install jq curl -y
        wget -q -O /root/cf "${CDN}/cf" >/dev/null 2>&1
        chmod +x /root/cf
        bash /root/cf | tee /root/install.log
        echo -e "${BGreen}Domain Random Done${NC}"
    elif [[ "$dns" == "2" ]]; then
        read -rp "Enter Your Domain : " dom
        dom="${dom//[[:space:]]/}"
        mkdir -p /etc/xray /etc/v2ray
        echo "$dom" > /root/scdomain
        echo "$dom" > /etc/xray/scdomain
        echo "$dom" > /etc/xray/domain
        echo "$dom" > /etc/v2ray/domain
        echo "$dom" > /root/domain
        echo "IP=$dom" > /var/lib/ipvps.conf
    else
        echo "Not Found Argument"
        exit 1
    fi
    echo -e "${BGreen}Done!${NC}"
    sleep 2
    clear

# Install SSH
echo -e "\e[33m-----------------------------------\033[0m"
echo -e "$BGreen      Install SSH & Setup VPS         $NC"
echo -e "\e[33m-----------------------------------\033[0m"
sleep 0.5
clear
wget -q -O /root/ssh-vpn.sh "${REPO}/ssh/ssh-vpn.sh" && chmod +x /root/ssh-vpn.sh && bash /root/ssh-vpn.sh

# Install SSH Websocket
echo -e "\e[33m-----------------------------------\033[0m"
echo -e "$BGreen      Install SSH Websocket           $NC"
echo -e "\e[33m-----------------------------------\033[0m"
sleep 0.5
clear
wget -q -O /root/insshws.sh "${REPO}/sshws/insshws.sh" && chmod +x /root/insshws.sh && bash /root/insshws.sh

# Install Xray
echo -e "\e[33m-----------------------------------\033[0m"
echo -e "$BGreen          Install XRAY              $NC"
echo -e "\e[33m-----------------------------------\033[0m"
sleep 0.5
clear
wget -q -O /root/ins-xray.sh "${REPO}/xray/ins-xray.sh" && chmod +x /root/ins-xray.sh && bash /root/ins-xray.sh

clear
cat > /root/.profile << END
# ~/.profile: executed by Bourne-compatible login shells.
if [ "$BASH" ]; then
  if [ -f ~/.bashrc ]; then
    . ~/.bashrc
  fi
fi
tty -s && mesg n || true
clear
menu
END
chmod 644 /root/.profile

[ -f "/root/log-install.txt" ] && rm /root/log-install.txt >/dev/null 2>&1
[ -f "/etc/afak.conf" ] && rm /etc/afak.conf >/dev/null 2>&1
[ ! -f "/etc/log-create-ssh.log" ] && echo "Log SSH Account " > /etc/log-create-ssh.log
[ ! -f "/etc/log-create-vmess.log" ] && echo "Log Vmess Account " > /etc/log-create-vmess.log
[ ! -f "/etc/log-create-vless.log" ] && echo "Log Vless Account " > /etc/log-create-vless.log
[ ! -f "/etc/log-create-trojan.log" ] && echo "Log Trojan Account " > /etc/log-create-trojan.log
[ ! -f "/etc/log-create-shadowsocks.log" ] && echo "Log Shadowsocks Account " > /etc/log-create-shadowsocks.log

history -c
serverV=$( curl -sS ${REPO}/menu/versi )
echo $serverV > /opt/.ver
curl -sS ipv4.icanhazip.com > /etc/myipvps

echo ""
echo "=================================================================="  | tee -a log-install.txt
echo "      ___                                    ___         ___      "  | tee -a log-install.txt
echo "     /  /\        ___           ___         /  /\       /__/\     "  | tee -a log-install.txt
echo "    /  /:/_      /  /\         /__/\       /  /::\      \  \:\    "  | tee -a log-install.txt
echo "   /  /:/ /\    /  /:/         \  \:\     /  /:/\:\      \  \:\   "  | tee -a log-install.txt
echo "  /  /:/_/::\  /__/::\          \  \:\   /  /:/~/:/  _____\__\:\  "  | tee -a log-install.txt
echo " /__/:/__\/\:\ \__\/\:\__   ___  \__\:\ /__/:/ /:/  /__/::::::::\ "  | tee -a log-install.txt
echo " \  \:\ /~~/:/    \  \:\/\ /__/\ |  |:| \  \:\/:/   \  \:\~~\~~\/ "  | tee -a log-install.txt
echo "  \  \:\  /:/      \__\::/ \  \:\|  |:|  \  \::/     \  \:\  ~~~  "  | tee -a log-install.txt
echo "   \  \:\/:/       /__/:/   \  \:\__|:|   \  \:\      \  \:\      "  | tee -a log-install.txt
echo "    \  \::/        \__\/     \__\::::/     \  \:\      \  \:\     "  | tee -a log-install.txt
echo "     \__\/                       ~~~~       \__\/       \__\/ 1.0 "  | tee -a log-install.txt
echo "=================================================================="  | tee -a log-install.txt
echo ""
echo "   >>> Service & Port"  | tee -a log-install.txt
echo "   - OpenSSH                  : 22, 9696"  | tee -a log-install.txt
echo "   - SSH Websocket            : 80 (via Nginx -> ws-dropbear:2095)" | tee -a log-install.txt
echo "   - SSH SSL Websocket        : 443" | tee -a log-install.txt
echo "   - Stunnel4                 : 222, 777" | tee -a log-install.txt
echo "   - Dropbear                 : 109, 143" | tee -a log-install.txt
echo "   - Badvpn                   : 7100-7400" | tee -a log-install.txt
echo "   - Nginx                    : 81" | tee -a log-install.txt
echo "   - Vmess WS TLS             : 443" | tee -a log-install.txt
echo "   - Vless WS TLS             : 443" | tee -a log-install.txt
echo "   - Trojan WS TLS            : 443" | tee -a log-install.txt
echo "   - Shadowsocks WS TLS       : 443" | tee -a log-install.txt
echo "   - Vmess WS none TLS        : 80" | tee -a log-install.txt
echo "   - Vless WS none TLS        : 80" | tee -a log-install.txt
echo "   - Trojan WS none TLS       : 80" | tee -a log-install.txt
echo "   - Shadowsocks WS none TLS  : 80" | tee -a log-install.txt
echo "   - Vmess gRPC               : 443" | tee -a log-install.txt
echo "   - Vless gRPC               : 443" | tee -a log-install.txt
echo "   - Trojan gRPC              : 443" | tee -a log-install.txt
echo "   - Shadowsocks gRPC         : 443" | tee -a log-install.txt
echo "   - Panel Simpel (Web)       : 1313" | tee -a log-install.txt
echo ""
echo "=============================Contact==============================" | tee -a log-install.txt
echo "---------------------------t.me/fahrialimudin-----------------------------" | tee -a log-install.txt
echo "=================================================================="  | tee -a log-install.txt

rm -f /root/ins-xray.sh /root/insshws.sh /root/ssh-vpn.sh
secs_to_human "$((1776743353 - ${start}))" | tee -a log-install.txt

# ============================================================
# Install Panel Simpel (port 1313) - download langsung dari GitHub
# ============================================================
echo ""
echo -e "[ ${BGreen}INFO${NC} ] Memulai instalasi Panel Simpel (port 1313)..."
sleep 1
wget -q -O /root/install-panel.sh "${REPO}/panel-simpel/install-panel.sh"
if [ -s /root/install-panel.sh ]; then
    chmod +x /root/install-panel.sh
    sed -i -e 's/\r$//' /root/install-panel.sh
    bash /root/install-panel.sh
    rm -f /root/install-panel.sh
    echo -e "[ ${BGreen}INFO${NC} ] ✅ Panel Simpel selesai diinstall di port 1313"
else
    echo -e "[ ${BRed}WARNING${NC} ] Gagal download install-panel.sh dari GitHub"
fi
echo ""

echo -ne "[ ${yell}WARNING${NC} ] reboot now ? (y/n)? "
read answer
if [ "$answer" == "${answer#[Yy]}" ] ;then
    exit 0
else
    reboot
fi
