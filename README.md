# Autoscript SSH + Xray + Panel Simpel

Script instalasi otomatis untuk Ubuntu 24.04:
- SSH VPN (OpenSSH, Dropbear, Stunnel, BadVPN)
- SSH Websocket
- Xray (Vmess, Vless, Trojan, Shadowsocks)
- **Panel Simpel Web** (port 1313) — terinstall otomatis

---

## 📦 Cara Install (1x klik)

```bash
sysctl -w net.ipv6.conf.all.disable_ipv6=1 && sysctl -w net.ipv6.conf.default.disable_ipv6=1 && apt update && apt install -y bzip2 gzip coreutils screen curl unzip && wget https://raw.githubusercontent.com/fahrialimudin/xray-with-panel-simpel/main/setup.sh && chmod +x setup.sh && sed -i -e 's/\r$//' setup.sh && screen -S setup ./setup.sh
```

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
