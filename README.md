# Autoscript SSH + Xray + Panel Simpel

Script instalasi otomatis untuk **Ubuntu 22.04, 24.04** dan **Debian 12**:
- SSH VPN (OpenSSH, Dropbear, Stunnel, BadVPN)
- SSH Websocket
- Xray (Vmess, Vless, Trojan, Shadowsocks)
- **Panel Simpel Web** (port 1313) — terinstall otomatis

---

## 📦 Cara Install

### 🟠 Ubuntu 22.04 / 24.04 (1x paste)

```bash
sysctl -w net.ipv6.conf.all.disable_ipv6=1 && sysctl -w net.ipv6.conf.default.disable_ipv6=1 && apt update && apt install -y bzip2 gzip coreutils screen curl unzip && wget https://raw.githubusercontent.com/fahrialimudin/xray-with-panel-simpel/main/setup.sh && chmod +x setup.sh && sed -i -e 's/\r$//' setup.sh && screen -S setup ./setup.sh
```

---

### 🔵 Debian 12 (2x paste)

**PASTE 1** — jalankan dulu, tunggu selesai:

```bash
sysctl -w net.ipv6.conf.all.disable_ipv6=1 && sysctl -w net.ipv6.conf.default.disable_ipv6=1 && apt update && apt install -y bzip2 gzip coreutils screen curl unzip gnupg2 lsb-release && curl -sSLo /tmp/php.gpg https://packages.sury.org/php/apt.gpg && gpg --dearmor < /tmp/php.gpg > /etc/apt/trusted.gpg.d/sury-php.gpg && echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && apt update && apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common && ln -sf /usr/bin/python3 /usr/bin/python && mkdir -p /var/log/xray /run/xray /etc/xray && chown -R www-data:www-data /var/log/xray /run/xray && touch /var/log/xray/access.log /var/log/xray/error.log /var/log/xray/access2.log /var/log/xray/error2.log && chown www-data:www-data /var/log/xray/*.log && printf '[Unit]\nDescription=/etc/rc.local\nConditionPathExists=/etc/rc.local\n[Service]\nType=forking\nExecStart=/etc/rc.local start\nTimeoutSec=0\nStandardOutput=tty\nRemainAfterExit=yes\nSysVStartPriority=99\n[Install]\nWantedBy=multi-user.target\n' > /etc/systemd/system/rc-local.service && systemctl enable rc-local && echo "=== PASTE 1 DONE ==="
```

**PASTE 2** — setelah PASTE 1 selesai:

```bash
sysctl -w net.ipv6.conf.all.disable_ipv6=1 && sysctl -w net.ipv6.conf.default.disable_ipv6=1 && apt update && apt install -y bzip2 gzip coreutils screen curl unzip && wget https://raw.githubusercontent.com/fahrialimudin/xray-with-panel-simpel/main/setup.sh && chmod +x setup.sh && sed -i -e 's/\r$//' setup.sh && screen -S setup ./setup.sh
```

> Saat muncul menu pilih **`2`** → Enter → ketik domain → Enter

---

## 🖥️ Service & Port

| Service                       | Port         |
|-------------------------------|--------------|
| OpenSSH                       | 22, 9696     |
| SSH Websocket                 | 80           |
| SSH SSL Websocket             | 443          |
| Stunnel4                      | 222, 777     |
| Dropbear                      | 109, 143     |
| Badvpn                        | 7100–7400    |
| Nginx                         | 81           |
| Vmess / Vless / Trojan WS TLS | 443          |
| Vmess / Vless / Trojan WS     | 80           |
| gRPC (semua protokol)         | 443          |
| **Panel Simpel (Web)**        | **1313**     |

---

## 📱 Akses Panel

Setelah install selesai, akses panel di browser:
```
http://IP_VPS:1313
```

---

Contact: t.me/fahrialimudin
