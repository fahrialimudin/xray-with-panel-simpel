<?php
$domain = '';
if (file_exists('/etc/xray/domain')) $domain = trim(file_get_contents('/etc/xray/domain'));
if (!$domain && file_exists('/etc/myipvps')) $domain = trim(file_get_contents('/etc/myipvps'));
if (!$domain) $domain = $_SERVER['HTTP_HOST'] ?? '';
$domain = explode(':', $domain)[0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VPN Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
  --blue:       #1a56db;
  --blue-mid:   #2563eb;
  --blue-light: #eff6ff;
  --blue-pale:  #dbeafe;
  --navy:       #1e3a5f;
  --white:      #ffffff;
  --gray-50:    #f8fafc;
  --gray-100:   #f1f5f9;
  --gray-200:   #e2e8f0;
  --gray-300:   #cbd5e1;
  --gray-400:   #94a3b8;
  --gray-500:   #64748b;
  --gray-600:   #475569;
  --gray-700:   #334155;
  --gray-800:   #1e293b;
  --green:      #10b981;
  --green-bg:   #ecfdf5;
  --red:        #ef4444;
  --red-bg:     #fef2f2;
  --orange:     #f59e0b;
  --shadow-xs:  0 1px 2px rgba(0,0,0,0.05);
  --shadow-sm:  0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
  --shadow:     0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
  --shadow-md:  0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.05);
  --radius:     12px;
  --radius-sm:  8px;
  --radius-xs:  6px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--gray-50);color:var(--gray-800);min-height:100vh;display:flex;}

/* SIDEBAR */
.sidebar{position:fixed;left:0;top:0;bottom:0;width:240px;background:var(--white);border-right:1px solid var(--gray-200);display:flex;flex-direction:column;z-index:100;transition:transform .25s;}
.sidebar-logo{padding:22px 20px 18px;border-bottom:1px solid var(--gray-100);}
.logo-wrap{display:flex;align-items:center;gap:10px;}
.logo-icon{width:38px;height:38px;background:linear-gradient(135deg,var(--blue),#3b82f6);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:19px;box-shadow:0 4px 10px rgba(26,86,219,.25);}
.logo-title{font-size:16px;font-weight:800;color:var(--navy);letter-spacing:-.3px;}
.logo-sub{font-size:11px;color:var(--gray-400);font-weight:400;margin-top:1px;}
.server-pill{display:inline-flex;align-items:center;gap:5px;background:var(--blue-light);border:1px solid var(--blue-pale);border-radius:20px;padding:4px 10px;font-size:11px;color:var(--blue);font-weight:600;margin-top:10px;}
.dot-live{width:6px;height:6px;border-radius:50%;background:var(--green);animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}

.sidebar-nav{flex:1;padding:14px 12px;overflow-y:auto;}
.nav-section{font-size:10px;font-weight:700;letter-spacing:.08em;color:var(--gray-400);text-transform:uppercase;padding:0 8px;margin:14px 0 5px;}
.nav-item{display:flex;align-items:center;gap:9px;padding:8px 10px;border-radius:var(--radius-sm);cursor:pointer;font-size:13.5px;font-weight:500;color:var(--gray-600);transition:all .15s;margin-bottom:1px;border:none;background:none;width:100%;text-align:left;}
.nav-item:hover{background:var(--blue-light);color:var(--blue);}
.nav-item.active{background:var(--blue-light);color:var(--blue);font-weight:600;}
.nav-item.active .nav-icon{background:var(--blue);color:white;}
.nav-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:14px;background:var(--gray-100);transition:all .15s;flex-shrink:0;}
.nav-item:hover .nav-icon{background:var(--blue-pale);}

/* MAIN */
.main{margin-left:240px;flex:1;min-height:100vh;}
.topbar{background:var(--white);border-bottom:1px solid var(--gray-200);padding:0 28px;height:58px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:var(--shadow-xs);}
.topbar-title{font-size:17px;font-weight:700;color:var(--gray-800);}
.topbar-right{display:flex;align-items:center;gap:10px;}
.ip-badge{display:flex;align-items:center;gap:5px;background:var(--gray-100);padding:5px 11px;border-radius:20px;font-size:12px;color:var(--gray-600);font-family:'JetBrains Mono',monospace;font-weight:500;}

/* CONTENT */
.content{padding:24px 28px;}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;}
.stat-card{background:var(--white);border-radius:var(--radius);padding:18px 20px;border:1px solid var(--gray-200);box-shadow:var(--shadow-xs);transition:box-shadow .2s;}
.stat-card:hover{box-shadow:var(--shadow-sm);}
.stat-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.stat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;}
.stat-icon.blue{background:var(--blue-light);}
.stat-icon.green{background:var(--green-bg);}
.stat-icon.orange{background:#fffbeb;}
.stat-icon.purple{background:#f5f3ff;}
.stat-val{font-size:26px;font-weight:800;color:var(--gray-800);line-height:1;margin-bottom:3px;}
.stat-label{font-size:12px;color:var(--gray-500);font-weight:500;}

/* CARDS */
.card{background:var(--white);border-radius:var(--radius);border:1px solid var(--gray-200);box-shadow:var(--shadow-xs);overflow:hidden;}
.card-header{padding:16px 20px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;justify-content:space-between;}
.card-title{font-size:14px;font-weight:700;color:var(--gray-800);display:flex;align-items:center;gap:7px;}
.card-body{padding:20px;}

/* GRID 2 COL */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}

/* FORM */
.form-group{margin-bottom:14px;}
.form-label{display:block;font-size:12px;font-weight:600;color:var(--gray-700);margin-bottom:5px;}
.form-input{width:100%;padding:9px 12px;border:1.5px solid var(--gray-200);border-radius:var(--radius-sm);font-size:13.5px;font-family:'Plus Jakarta Sans',sans-serif;color:var(--gray-800);background:var(--white);transition:border .15s;}
.form-input:focus{outline:none;border-color:var(--blue-mid);box-shadow:0 0 0 3px rgba(37,99,235,.08);}
.form-hint{font-size:11px;color:var(--gray-400);margin-top:3px;}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:var(--radius-sm);font-size:13px;font-weight:600;font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;border:none;transition:all .15s;}
.btn-primary{background:var(--blue-mid);color:white;box-shadow:0 1px 3px rgba(37,99,235,.3);}
.btn-primary:hover{background:var(--blue);box-shadow:0 4px 10px rgba(37,99,235,.35);}
.btn-primary:disabled{background:var(--gray-300);box-shadow:none;cursor:not-allowed;}
.btn-sm{padding:5px 11px;font-size:12px;}
.btn-danger{background:var(--red-bg);color:var(--red);border:1px solid #fecaca;}
.btn-danger:hover{background:#fee2e2;}
.btn-ghost{background:var(--gray-100);color:var(--gray-600);}
.btn-ghost:hover{background:var(--gray-200);}
.btn-full{width:100%;justify-content:center;}

/* RESULT BOX */
.result-box{border-radius:var(--radius-sm);padding:14px 16px;margin-top:14px;display:none;}
.result-box.success{background:var(--green-bg);border:1px solid #a7f3d0;}
.result-box.error{background:var(--red-bg);border:1px solid #fecaca;}
.result-title{font-size:13px;font-weight:700;margin-bottom:10px;display:flex;align-items:center;gap:5px;}
.result-title.ok{color:#065f46;}
.result-title.err{color:#991b1b;}
.result-row{display:flex;justify-content:space-between;align-items:flex-start;padding:5px 0;border-bottom:1px solid rgba(0,0,0,.05);gap:12px;}
.result-row:last-child{border:none;}
.result-key{font-size:11.5px;color:var(--gray-500);font-weight:600;white-space:nowrap;min-width:80px;}
.result-val{font-size:12px;color:var(--gray-800);font-family:'JetBrains Mono',monospace;word-break:break-all;text-align:right;}
.link-wrap{display:flex;align-items:center;gap:6px;width:100%;justify-content:flex-end;}
.copy-btn{background:var(--blue-light);border:none;border-radius:4px;padding:2px 8px;font-size:10px;color:var(--blue);cursor:pointer;font-weight:600;white-space:nowrap;transition:all .15s;}
.copy-btn:hover{background:var(--blue-pale);}
.copy-btn.copied{background:#d1fae5;color:#065f46;}
.output-box{background:#0f172a;border-radius:var(--radius-sm);padding:14px 16px;margin-top:10px;position:relative;}
.output-pre{font-family:'JetBrains Mono',monospace;font-size:11.5px;color:#e2e8f0;white-space:pre-wrap;word-break:break-all;line-height:1.7;margin:0;}
.copy-all-btn{position:absolute;top:10px;right:10px;background:#1e40af;color:#fff;border:none;border-radius:5px;padding:4px 12px;font-size:11px;font-weight:700;cursor:pointer;transition:all .15s;}
.copy-all-btn:hover{background:#1d4ed8;}
.copy-all-btn.copied{background:#065f46;}

/* TABS */
.tabs{display:flex;gap:4px;background:var(--gray-100);padding:4px;border-radius:var(--radius-sm);margin-bottom:16px;width:fit-content;}
.tab{padding:6px 14px;border-radius:6px;font-size:12.5px;font-weight:600;cursor:pointer;color:var(--gray-500);transition:all .15s;border:none;background:none;}
.tab.active{background:var(--white);color:var(--blue);box-shadow:var(--shadow-xs);}

/* TABLE */
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{font-size:11px;font-weight:700;color:var(--gray-400);text-transform:uppercase;letter-spacing:.05em;padding:8px 12px;border-bottom:2px solid var(--gray-100);}
td{font-size:13px;padding:10px 12px;border-bottom:1px solid var(--gray-100);color:var(--gray-700);}
tr:last-child td{border:none;}
tr:hover td{background:var(--gray-50);}
.badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-green{background:var(--green-bg);color:#065f46;}
.badge-red{background:var(--red-bg);color:#991b1b;}
.badge-blue{background:var(--blue-light);color:var(--blue);}
.badge-gray{background:var(--gray-100);color:var(--gray-500);}

/* SERVICE STATUS */
.svc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;}
.svc-item{background:var(--gray-50);border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:12px 14px;display:flex;align-items:center;justify-content:space-between;}
.svc-name{font-size:13px;font-weight:600;color:var(--gray-700);}
.svc-status{font-size:11px;font-weight:700;}
.svc-status.active{color:var(--green);}
.svc-status.inactive{color:var(--red);}

/* SPINNER */
.spinner{width:16px;height:16px;border:2.5px solid rgba(255,255,255,.3);border-top-color:white;border-radius:50%;animation:spin .7s linear infinite;display:none;}
@keyframes spin{to{transform:rotate(360deg)}}

/* PAGE SECTIONS */
.page{display:none;}
.page.active{display:block;}

/* HAMBURGER */
.hamburger{display:none;background:none;border:none;cursor:pointer;padding:6px;border-radius:6px;}
.hamburger span{display:block;width:20px;height:2px;background:var(--gray-600);margin:4px 0;transition:all .2s;}

/* OVERLAY */
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:90;}

/* RESPONSIVE */
@media(max-width:900px){
  .sidebar{transform:translateX(-100%);}
  .sidebar.open{transform:translateX(0);}
  .main{margin-left:0;}
  .hamburger{display:block;}
  .overlay.show{display:block;}
  .stats-grid{grid-template-columns:1fr 1fr;}
  .grid-2{grid-template-columns:1fr;}
  .svc-grid{grid-template-columns:1fr 1fr;}
}
@media(max-width:500px){
  .stats-grid{grid-template-columns:1fr 1fr;}
  .content{padding:16px;}
  .svc-grid{grid-template-columns:1fr;}
}
</style>
</head>
<body>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-wrap">
      <div class="logo-icon">🛡️</div>
      <div>
        <div class="logo-title">VPN Panel</div>
        <div class="logo-sub">xray-optimized</div>
      </div>
    </div>
    <div class="server-pill"><span class="dot-live"></span><?= htmlspecialchars($domain) ?></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Dashboard</div>
    <button class="nav-item active" onclick="showPage('dashboard',this)">
      <span class="nav-icon">📊</span> Dashboard
    </button>
    <button class="nav-item" onclick="showPage('services',this)">
      <span class="nav-icon">⚙️</span> Status Service
    </button>

    <div class="nav-section">Akun SSH</div>
    <button class="nav-item" onclick="showPage('ssh',this)">
      <span class="nav-icon">🔑</span> Buat SSH
    </button>

    <div class="nav-section">Akun Xray</div>
    <button class="nav-item" onclick="showPage('vmess',this)">
      <span class="nav-icon">⚡</span> Vmess
    </button>
    <button class="nav-item" onclick="showPage('vless',this)">
      <span class="nav-icon">🚀</span> Vless
    </button>
    <button class="nav-item" onclick="showPage('trojan',this)">
      <span class="nav-icon">🛡️</span> Trojan
    </button>
    <button class="nav-item" onclick="showPage('ss',this)">
      <span class="nav-icon">🔒</span> Shadowsocks
    </button>

    <div class="nav-section">Manajemen</div>
    <button class="nav-item" onclick="showPage('users',this)">
      <span class="nav-icon">👥</span> Daftar User
    </button>
  </nav>
</aside>

<!-- MAIN -->
<div class="main">
  <header class="topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <button class="hamburger" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
      <span class="topbar-title" id="page-title">Dashboard</span>
    </div>
    <div class="topbar-right">
      <div class="ip-badge">🌐 <?= htmlspecialchars($domain) ?></div>
    </div>
  </header>

  <div class="content">

    <!-- DASHBOARD -->
    <div class="page active" id="page-dashboard">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-top"><div></div><div class="stat-icon blue">👤</div></div>
          <div class="stat-val" id="s-ssh">—</div>
          <div class="stat-label">User SSH</div>
        </div>
        <div class="stat-card">
          <div class="stat-top"><div></div><div class="stat-icon green">⚡</div></div>
          <div class="stat-val" id="s-xray">—</div>
          <div class="stat-label">User Xray</div>
        </div>
        <div class="stat-card">
          <div class="stat-top"><div></div><div class="stat-icon orange">🧠</div></div>
          <div class="stat-val" id="s-ram" style="font-size:18px">—</div>
          <div class="stat-label">RAM</div>
        </div>
        <div class="stat-card">
          <div class="stat-top"><div></div><div class="stat-icon purple">⏱️</div></div>
          <div class="stat-val" id="s-uptime" style="font-size:20px">—</div>
          <div class="stat-label">Uptime</div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <span class="card-title">📋 Info Port & Protokol</span>
        </div>
        <div class="card-body">
          <div class="grid-2">
            <div>
              <table>
                <thead><tr><th>Service</th><th>Port</th></tr></thead>
                <tbody>
                  <tr><td>OpenSSH</td><td><code>22, 9696</code></td></tr>
                  <tr><td>Dropbear</td><td><code>109, 143</code></td></tr>
                  <tr><td>Stunnel4 (SSL)</td><td><code>222, 777</code></td></tr>
                  <tr><td>SSH WS (HTTP)</td><td><code>80</code></td></tr>
                  <tr><td>SSH WSS (HTTPS)</td><td><code>443</code></td></tr>
                  <tr><td>BadVPN UDPGW</td><td><code>7100-7400</code></td></tr>
                </tbody>
              </table>
            </div>
            <div>
              <table>
                <thead><tr><th>Xray Protocol</th><th>Port</th></tr></thead>
                <tbody>
                  <tr><td>Vmess WS</td><td><code>80, 443</code></td></tr>
                  <tr><td>Vless WS</td><td><code>80, 443</code></td></tr>
                  <tr><td>Trojan WS</td><td><code>80, 443</code></td></tr>
                  <tr><td>Shadowsocks WS</td><td><code>80, 443</code></td></tr>
                  <tr><td>Vmess/Vless/Trojan gRPC</td><td><code>443</code></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- STATUS SERVICE -->
    <div class="page" id="page-services">
      <div class="card">
        <div class="card-header">
          <span class="card-title">⚙️ Status Service</span>
          <button class="btn btn-ghost btn-sm" onclick="loadServices()">🔄 Refresh</button>
        </div>
        <div class="card-body">
          <div class="svc-grid" id="svc-grid"><div style="color:var(--gray-400);font-size:13px">Memuat...</div></div>
        </div>
      </div>
    </div>

    <!-- SSH -->
    <div class="page" id="page-ssh">
      <div class="grid-2">
        <div class="card">
          <div class="card-header"><span class="card-title">🔑 Buat Akun SSH</span></div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label">Username</label>
              <input class="form-input" id="ssh-user" placeholder="contoh: john123" maxlength="20">
            </div>
            <div class="form-group">
              <label class="form-label">Password</label>
              <input class="form-input" id="ssh-pass" placeholder="minimal 4 karakter" type="password">
            </div>
            <div class="form-group">
              <label class="form-label">Masa Aktif (Hari)</label>
              <input class="form-input" id="ssh-exp" type="number" value="30" min="1" max="365">
            </div>
            <button class="btn btn-primary btn-full" id="ssh-btn" onclick="createSsh()">
              <span class="spinner" id="ssh-spin"></span>
              🔑 Buat SSH Account
            </button>
            <div class="result-box" id="ssh-result"></div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title">ℹ️ Info SSH</span></div>
          <div class="card-body">
            <p style="font-size:13px;color:var(--gray-500);margin-bottom:14px">Akun SSH digunakan untuk koneksi tunnel via OpenSSH, Dropbear, Stunnel, dan WebSocket.</p>
            <div style="background:var(--gray-50);border-radius:8px;padding:12px;font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--gray-600);line-height:1.8">
              Payload WS:<br>
              GET / HTTP/1.1[crlf]<br>
              Host: <?= htmlspecialchars($domain) ?>[crlf]<br>
              Upgrade: websocket[crlf][crlf]
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- VMESS -->
    <div class="page" id="page-vmess">
      <div class="grid-2">
        <div class="card">
          <div class="card-header"><span class="card-title">⚡ Buat Akun Vmess</span></div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label">Username</label>
              <input class="form-input" id="vmess-user" placeholder="contoh: john123" maxlength="20">
            </div>
            <div class="form-group">
              <label class="form-label">Masa Aktif (Hari)</label>
              <input class="form-input" id="vmess-exp" type="number" value="30" min="1" max="365">
            </div>
            <button class="btn btn-primary btn-full" id="vmess-btn" onclick="createXray('vmess')">
              <span class="spinner" id="vmess-spin"></span>
              ⚡ Buat Vmess Account
            </button>
            <div class="result-box" id="vmess-result"></div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title">ℹ️ Info Vmess</span></div>
          <div class="card-body">
            <div style="font-size:13px;color:var(--gray-600);line-height:1.7">
              <p><strong>Network:</strong> WebSocket (WS) + gRPC</p>
              <p><strong>Port TLS:</strong> 443</p>
              <p><strong>Port HTTP:</strong> 80</p>
              <p><strong>Path WS:</strong> /vmess</p>
              <p><strong>gRPC Service:</strong> vmess-grpc</p>
              <p><strong>TLS:</strong> Ya (port 443)</p>
              <p><strong>AlterID:</strong> 0</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- VLESS -->
    <div class="page" id="page-vless">
      <div class="grid-2">
        <div class="card">
          <div class="card-header"><span class="card-title">🚀 Buat Akun Vless</span></div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label">Username</label>
              <input class="form-input" id="vless-user" placeholder="contoh: john123" maxlength="20">
            </div>
            <div class="form-group">
              <label class="form-label">Masa Aktif (Hari)</label>
              <input class="form-input" id="vless-exp" type="number" value="30" min="1" max="365">
            </div>
            <button class="btn btn-primary btn-full" id="vless-btn" onclick="createXray('vless')">
              <span class="spinner" id="vless-spin"></span>
              🚀 Buat Vless Account
            </button>
            <div class="result-box" id="vless-result"></div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title">ℹ️ Info Vless</span></div>
          <div class="card-body">
            <div style="font-size:13px;color:var(--gray-600);line-height:1.7">
              <p><strong>Network:</strong> WebSocket (WS) + gRPC</p>
              <p><strong>Port TLS:</strong> 443</p>
              <p><strong>Port HTTP:</strong> 80</p>
              <p><strong>Path WS:</strong> /vless</p>
              <p><strong>gRPC Service:</strong> vless-grpc</p>
              <p><strong>Encryption:</strong> none</p>
              <p><strong>Security:</strong> TLS (port 443)</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TROJAN -->
    <div class="page" id="page-trojan">
      <div class="grid-2">
        <div class="card">
          <div class="card-header"><span class="card-title">🛡️ Buat Akun Trojan</span></div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label">Username</label>
              <input class="form-input" id="trojan-user" placeholder="contoh: john123" maxlength="20">
            </div>
            <div class="form-group">
              <label class="form-label">Masa Aktif (Hari)</label>
              <input class="form-input" id="trojan-exp" type="number" value="30" min="1" max="365">
            </div>
            <button class="btn btn-primary btn-full" id="trojan-btn" onclick="createXray('trojan')">
              <span class="spinner" id="trojan-spin"></span>
              🛡️ Buat Trojan Account
            </button>
            <div class="result-box" id="trojan-result"></div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title">ℹ️ Info Trojan</span></div>
          <div class="card-body">
            <div style="font-size:13px;color:var(--gray-600);line-height:1.7">
              <p><strong>Network:</strong> WebSocket (WS) + gRPC</p>
              <p><strong>Port TLS:</strong> 443</p>
              <p><strong>Port HTTP:</strong> 80</p>
              <p><strong>Path WS:</strong> /trojan-ws</p>
              <p><strong>gRPC Service:</strong> trojan-grpc</p>
              <p><strong>Security:</strong> TLS (port 443)</p>
              <p><strong>Password:</strong> Auto-generate UUID</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SHADOWSOCKS -->
    <div class="page" id="page-ss">
      <div class="grid-2">
        <div class="card">
          <div class="card-header"><span class="card-title">🔒 Buat Akun Shadowsocks</span></div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label">Username</label>
              <input class="form-input" id="ss-user" placeholder="contoh: john123" maxlength="20">
            </div>
            <div class="form-group">
              <label class="form-label">Masa Aktif (Hari)</label>
              <input class="form-input" id="ss-exp" type="number" value="30" min="1" max="365">
            </div>
            <button class="btn btn-primary btn-full" id="ss-btn" onclick="createXray('ss')">
              <span class="spinner" id="ss-spin"></span>
              🔒 Buat SS Account
            </button>
            <div class="result-box" id="ss-result"></div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title">ℹ️ Info Shadowsocks</span></div>
          <div class="card-body">
            <div style="font-size:13px;color:var(--gray-600);line-height:1.7">
              <p><strong>Method:</strong> aes-128-gcm</p>
              <p><strong>Network:</strong> WebSocket (WS)</p>
              <p><strong>Port TLS:</strong> 443</p>
              <p><strong>Port HTTP:</strong> 80</p>
              <p><strong>Path WS:</strong> /ss-ws</p>
              <p><strong>Plugin:</strong> v2ray-plugin</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- USERS -->
    <div class="page" id="page-users">
      <div class="card">
        <div class="card-header">
          <span class="card-title">👥 Daftar User</span>
          <button class="btn btn-ghost btn-sm" onclick="loadUsers()">🔄 Refresh</button>
        </div>
        <div class="card-body">
          <div class="tabs">
            <button class="tab active" onclick="switchTab('ssh-tab',this)">SSH</button>
            <button class="tab" onclick="switchTab('xray-tab',this)">Xray</button>
          </div>
          <div id="ssh-tab" class="table-wrap">
            <table>
              <thead><tr><th>Username</th><th>Expired</th><th>Status</th><th>Aksi</th></tr></thead>
              <tbody id="ssh-tbody"><tr><td colspan="4" style="text-align:center;color:var(--gray-400)">Memuat...</td></tr></tbody>
            </table>
          </div>
          <div id="xray-tab" class="table-wrap" style="display:none">
            <table>
              <thead><tr><th>Username</th><th>Tipe</th><th>Expired</th><th>Aksi</th></tr></thead>
              <tbody id="xray-tbody"><tr><td colspan="4" style="text-align:center;color:var(--gray-400)">Memuat...</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<script>
const API = 'api.php';

async function api(data) {
  const r = await fetch(API, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
  return r.json();
}

// NAV
function showPage(name, btn) {
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(b=>b.classList.remove('active'));
  document.getElementById('page-'+name).classList.add('active');
  btn.classList.add('active');
  const titles = {dashboard:'Dashboard',services:'Status Service',ssh:'Buat SSH',vmess:'Buat Vmess',vless:'Buat Vless',trojan:'Buat Trojan',ss:'Buat Shadowsocks',users:'Daftar User'};
  document.getElementById('page-title').textContent = titles[name]||name;
  if (name==='services') loadServices();
  if (name==='users') loadUsers();
  if (name==='dashboard') loadStats();
  closeSidebar();
}
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('overlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('show');}

// TABS
function switchTab(id, btn) {
  document.querySelectorAll('[id$="-tab"]').forEach(t=>t.style.display='none');
  document.querySelectorAll('.tab').forEach(b=>b.classList.remove('active'));
  document.getElementById(id).style.display='block';
  btn.classList.add('active');
}

// STATS
async function loadStats() {
  const d = await api({action:'stats'});
  if (d.success) {
    document.getElementById('s-ssh').textContent = d.data.ssh_users;
    document.getElementById('s-xray').textContent = d.data.xray_users;
    document.getElementById('s-ram').textContent = d.data.ram;
    document.getElementById('s-uptime').textContent = d.data.uptime;
  }
}

// SERVICES
async function loadServices() {
  const d = await api({action:'service_status'});
  const g = document.getElementById('svc-grid');
  if (!d.success){g.innerHTML='<p style="color:red">Gagal memuat</p>';return;}
  g.innerHTML = d.data.map(s=>`
    <div class="svc-item">
      <div>
        <div class="svc-name">${s.name}</div>
      </div>
      <div>
        <span class="svc-status ${s.status==='active'?'active':'inactive'}">${s.status==='active'?'● Active':'● Inactive'}</span>
        ${s.status==='active'?`<button class="btn btn-ghost btn-sm" style="margin-top:4px;font-size:10px" onclick="restartSvc('${s.svc}',this)">Restart</button>`:''}
      </div>
    </div>`).join('');
}
async function restartSvc(name, btn) {
  btn.disabled=true; btn.textContent='...';
  const d = await api({action:'restart_service',name});
  btn.disabled=false; btn.textContent='Restart';
  alert(d.message);
  loadServices();
}

// CREATE SSH
async function createSsh() {
  const user=document.getElementById('ssh-user').value.trim();
  const pass=document.getElementById('ssh-pass').value.trim();
  const exp=parseInt(document.getElementById('ssh-exp').value)||30;
  const btn=document.getElementById('ssh-btn');
  const spin=document.getElementById('ssh-spin');
  btn.disabled=true; spin.style.display='inline-block';
  const d = await api({action:'create_ssh',user,pass,exp});
  btn.disabled=false; spin.style.display='none';
  const box=document.getElementById('ssh-result');
  if (d.success) {
    const r=d.data;
    box.className='result-box success'; box.style.display='block';
    const outText = r.output || `Username: ${r.user}\nPassword: ${r.pass}\nExpired: ${r.exp_date}\nHost: ${r.host}`;
    const safeText = outText.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const uid='out'+Math.random().toString(36).substr(2,6);
    box.innerHTML=`<div class="result-title ok">✅ SSH Account Berhasil Dibuat!</div>
      <div class="output-box">
        <button class="copy-all-btn" id="${uid}" onclick="copyAll('${uid}',this.dataset.txt)" data-txt="${escAttr(outText)}">📋 Salin Semua</button>
        <pre class="output-pre">${safeText}</pre>
      </div>`;
    document.getElementById('ssh-user').value='';
    document.getElementById('ssh-pass').value='';
    loadStats();
  } else {
    box.className='result-box error'; box.style.display='block';
    box.innerHTML=`<div class="result-title err">❌ Gagal</div><div style="font-size:12px;color:#991b1b">${d.message}</div>`;
  }
}

// CREATE XRAY
async function createXray(proto) {
  const user=document.getElementById(proto+'-user').value.trim();
  const exp=parseInt(document.getElementById(proto+'-exp').value)||30;
  const btn=document.getElementById(proto+'-btn');
  const spin=document.getElementById(proto+'-spin');
  btn.disabled=true; spin.style.display='inline-block';
  const d = await api({action:'create_'+proto,user,exp});
  btn.disabled=false; spin.style.display='none';
  const box=document.getElementById(proto+'-result');
  if (d.success) {
    const r=d.data;
    box.className='result-box success'; box.style.display='block';
    const outText = r.output || `User: ${r.user}\nHost: ${r.host}\nExpired: ${r.exp_date}`;
    const safeText = outText.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const uid='out'+Math.random().toString(36).substr(2,6);
    box.innerHTML=`<div class="result-title ok">✅ Account Berhasil Dibuat!</div>
      <div class="output-box">
        <button class="copy-all-btn" id="${uid}" onclick="copyAll('${uid}',this.dataset.txt)" data-txt="${escAttr(outText)}">📋 Salin Semua</button>
        <pre class="output-pre">${safeText}</pre>
      </div>`;
    document.getElementById(proto+'-user').value='';
    loadStats();
  } else {
    box.className='result-box error'; box.style.display='block';
    box.innerHTML=`<div class="result-title err">❌ Gagal</div><div style="font-size:12px;color:#991b1b">${d.message}</div>`;
  }
}

function escAttr(s) {
  return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function row(k,v) {
  return `<div class="result-row"><span class="result-key">${k}</span><span class="result-val">${v}</span></div>`;
}
function linkRow(k,v) {
  const id='lnk'+Math.random().toString(36).substr(2,5);
  return `<div class="result-row"><span class="result-key">${k}</span>
    <span class="link-wrap">
      <span class="result-val" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${v}">${v.substring(0,30)}...</span>
      <button class="copy-btn" id="${id}" onclick="copyLink('${id}','${v}')">Copy</button>
    </span></div>`;
}
function copyLink(id, text) {
  navigator.clipboard.writeText(text).then(()=>{
    const b=document.getElementById(id);
    b.textContent='✓'; b.classList.add('copied');
    setTimeout(()=>{b.textContent='Copy';b.classList.remove('copied');},1500);
  });
}
function copyAll(id, text) {
  navigator.clipboard.writeText(text).then(()=>{
    const b=document.getElementById(id);
    const orig=b.textContent;
    b.textContent='✓ Tersalin!'; b.classList.add('copied');
    setTimeout(()=>{b.textContent=orig;b.classList.remove('copied');},2000);
  }).catch(()=>{
    // fallback for older browsers
    const ta=document.createElement('textarea');
    ta.value=text; document.body.appendChild(ta); ta.select();
    document.execCommand('copy'); document.body.removeChild(ta);
    const b=document.getElementById(id);
    const orig=b.textContent;
    b.textContent='✓ Tersalin!'; b.classList.add('copied');
    setTimeout(()=>{b.textContent=orig;b.classList.remove('copied');},2000);
  });
}

// LOAD USERS
async function loadUsers() {
  const d = await api({action:'list_users'});
  if (!d.success) return;
  const st = document.getElementById('ssh-tbody');
  const xt = document.getElementById('xray-tbody');
  if (d.data.ssh.length===0) {
    st.innerHTML='<tr><td colspan="4" style="text-align:center;color:var(--gray-400)">Tidak ada user SSH</td></tr>';
  } else {
    st.innerHTML = d.data.ssh.map(u=>`<tr>
      <td><strong>${u.user}</strong></td>
      <td style="font-family:'JetBrains Mono',monospace;font-size:12px">${u.exp}</td>
      <td><span class="badge ${u.status==='active'?'badge-green':'badge-red'}">${u.status}</span></td>
      <td><button class="btn btn-danger btn-sm" onclick="delUser('ssh','${u.user}')">🗑 Hapus</button></td>
    </tr>`).join('');
  }
  if (d.data.xray.length===0) {
    xt.innerHTML='<tr><td colspan="4" style="text-align:center;color:var(--gray-400)">Tidak ada user Xray</td></tr>';
  } else {
    xt.innerHTML = d.data.xray.map(u=>`<tr>
      <td><strong>${u.user}</strong></td>
      <td><span class="badge badge-blue">${u.type}</span></td>
      <td style="font-family:'JetBrains Mono',monospace;font-size:12px">${u.exp}</td>
      <td><button class="btn btn-danger btn-sm" onclick="delUser('xray','${u.user}','${u.type}')">🗑 Hapus</button></td>
    </tr>`).join('');
  }
}
async function delUser(type, user, proto) {
  if (!confirm(`Hapus user ${user}?`)) return;
  let d;
  if (type==='ssh') d = await api({action:'delete_ssh',user});
  else d = await api({action:'delete_xray',user,type:proto?.toLowerCase()});
  alert(d.message); loadUsers(); loadStats();
}

// INIT
loadStats();
setInterval(loadStats, 30000);
</script>
</body>
</html>
