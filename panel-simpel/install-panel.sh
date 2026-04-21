#!/bin/bash
# ============================================================
# Install VPN Panel v3
# Port: 1313
# Compatible: xray-optimized (Ubuntu 22/24)
# ============================================================
GREEN='\e[1;32m'; CYAN='\e[1;36m'; RED='\e[1;31m'; YELLOW='\e[1;33m'; NC='\e[0m'
REPO="https://raw.githubusercontent.com/fahrialimudin/xray-with-panel-simpel/main"

echo -e "${CYAN}============================================${NC}"
echo -e "${CYAN}     Install VPN Panel v3                  ${NC}"
echo -e "${CYAN}     Port: 1313                            ${NC}"
echo -e "${CYAN}============================================${NC}"

[ "$EUID" -ne 0 ] && echo -e "${RED}Jalankan sebagai root!${NC}" && exit 1

# [1] Install PHP
echo -e "${GREEN}[1/6] Install PHP...${NC}"
apt-get update -y >/dev/null 2>&1
apt-get install -y php8.3 php8.3-fpm php8.3-cli php8.3-common >/dev/null 2>&1 || \
apt-get install -y php php-fpm php-cli php-common >/dev/null 2>&1
PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null)
echo -e "${GREEN}   PHP: $PHP_VER${NC}"

# [2] Start PHP-FPM
echo -e "${GREEN}[2/6] Start PHP-FPM...${NC}"
systemctl enable php${PHP_VER}-fpm >/dev/null 2>&1
systemctl start  php${PHP_VER}-fpm >/dev/null 2>&1
sleep 2
SOCK=$(ls /run/php/php${PHP_VER}-fpm.sock 2>/dev/null || ls /run/php/*.sock 2>/dev/null | head -1)
if [ -z "$SOCK" ]; then
    echo -e "${RED}   ERROR: PHP-FPM socket tidak ditemukan!${NC}"
    exit 1
fi
echo -e "${GREEN}   Socket: $SOCK${NC}"

# [3] Download & deploy file panel langsung dari GitHub
echo -e "${GREEN}[3/6] Download & deploy file panel...${NC}"
mkdir -p /home/vps/public_html
wget -q -O /home/vps/public_html/index.php "${REPO}/panel-simpel/public/index.php"
wget -q -O /home/vps/public_html/api.php   "${REPO}/panel-simpel/public/api.php"
if [ ! -s /home/vps/public_html/index.php ] || [ ! -s /home/vps/public_html/api.php ]; then
    echo -e "${RED}   ERROR: Gagal download file panel dari GitHub!${NC}"
    exit 1
fi
chown -R www-data:www-data /home/vps/public_html
chmod 755 /home/vps/public_html
chmod 644 /home/vps/public_html/*.php
echo -e "${GREEN}   ✅ File panel berhasil di-deploy${NC}"

# [4] Sudoers
echo -e "${GREEN}[4/6] Setup sudoers...${NC}"
cat > /etc/sudoers.d/vpnpanel <<-EOF
www-data ALL=(ALL) NOPASSWD: ALL
EOF
chmod 440 /etc/sudoers.d/vpnpanel
echo -e "${GREEN}   ✅ Sudoers terkonfigurasi${NC}"

# [5] Konfigurasi Nginx port 1313
echo -e "${GREEN}[5/6] Konfigurasi Nginx (port 1313)...${NC}"
DOMAIN=""
[ -f /etc/xray/domain ] && DOMAIN=$(cat /etc/xray/domain 2>/dev/null)
[ -z "$DOMAIN" ] && [ -f /etc/myipvps ] && DOMAIN=$(cat /etc/myipvps 2>/dev/null)
[ -z "$DOMAIN" ] && DOMAIN=$(curl -s --max-time 5 ifconfig.me 2>/dev/null)
[ -z "$DOMAIN" ] && DOMAIN=$(hostname -I | awk '{print $1}')
echo -e "${GREEN}   Domain/IP: $DOMAIN${NC}"

rm -f /etc/nginx/conf.d/vpn-panel.conf
cat > /etc/nginx/conf.d/vpn-panel.conf <<NGINXEOF
server {
    listen 1313;
    server_name _;
    root /home/vps/public_html;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${SOCK};
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht { deny all; }
}
NGINXEOF
echo -e "${GREEN}   ✅ Nginx config dibuat (port 1313)${NC}"

# [6] Test & Reload Nginx
echo -e "${GREEN}[6/6] Test & Reload Nginx...${NC}"
if nginx -t >/dev/null 2>&1; then
    systemctl reload nginx
    echo -e "${GREEN}   ✅ Nginx reload berhasil${NC}"
else
    echo -e "${RED}   ❌ Nginx config error:${NC}"
    nginx -t
    exit 1
fi

sleep 2
RESULT=$(curl -s -X POST http://localhost:1313/api.php \
  -H "Content-Type: application/json" \
  -d '{"action":"stats"}')
if echo "$RESULT" | grep -q '"success":true'; then
    echo -e "${GREEN}   ✅ API berjalan normal di port 1313!${NC}"
else
    echo -e "${YELLOW}   ⚠️  API response: $RESULT${NC}"
fi

echo ""
echo -e "${CYAN}============================================${NC}"
echo -e "${CYAN}   Panel berhasil diinstall!               ${NC}"
echo -e "${CYAN}============================================${NC}"
echo ""
echo -e " Akses panel : ${GREEN}http://${DOMAIN}:1313${NC}"
echo ""
echo -e " Jika firewall aktif:"
echo -e " ${YELLOW}ufw allow 1313/tcp${NC}"
echo ""
