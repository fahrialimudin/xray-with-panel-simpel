<?php
// ============================================================
// VPN Panel API v3 - Fixed
// - SSH: passwd unlock fix (echo pass|passwd)
// - Xray: sed inject via shell script temp file (100% sama bash)
// - Port: 1313
// ============================================================
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

define('XRAY_CONFIG', '/etc/xray/config.json');
define('DOMAIN_FILE', '/etc/xray/domain');
define('MY_IP_FILE',  '/etc/myipvps');
define('IPVPS_CONF',  '/var/lib/ipvps.conf');
define('LOG_INSTALL', '/root/log-install.txt');
define('SSH_LOG',     '/etc/log-create-ssh.log');
define('VMESS_LOG',   '/etc/log-create-vmess.log');
define('VLESS_LOG',   '/etc/log-create-vless.log');
define('TROJAN_LOG',  '/etc/log-create-trojan.log');
define('SS_LOG',      '/etc/log-create-shadowsocks.log');

function ok($data = [], $msg = 'OK') {
    echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data]); exit;
}
function fail($msg = 'Error') {
    echo json_encode(['success'=>false,'message'=>$msg,'data'=>null]); exit;
}

// Jalankan perintah bash via sudo, return output
function run($cmd) {
    return trim(shell_exec('sudo bash -c ' . escapeshellarg($cmd) . ' 2>&1') ?? '');
}

// Jalankan script bash sementara (untuk sed inject agar 100% sama dengan autoscript)
function runScript($script) {
    $tmp = tempnam('/tmp', 'vpnpanel_');
    file_put_contents($tmp, "#!/bin/bash\n" . $script);
    chmod($tmp, 0755);
    $out = shell_exec('sudo bash ' . escapeshellarg($tmp) . ' 2>&1');
    @unlink($tmp);
    return trim($out ?? '');
}

function getDomain() {
    // Sama persis urutan check seperti autoscript
    if (file_exists(IPVPS_CONF)) {
        $conf = file_get_contents(IPVPS_CONF);
        if (preg_match('/^IP=(.+)$/m', $conf, $m) && trim($m[1])) {
            return trim($m[1]);
        }
    }
    if (file_exists(DOMAIN_FILE)) {
        $d = trim(file_get_contents(DOMAIN_FILE));
        if ($d) return $d;
    }
    if (file_exists(MY_IP_FILE)) {
        $d = trim(file_get_contents(MY_IP_FILE));
        if ($d) return $d;
    }
    return run('curl -s ifconfig.me');
}

function getIP() {
    if (file_exists(MY_IP_FILE)) return trim(file_get_contents(MY_IP_FILE));
    return run('curl -s ifconfig.me');
}

function validUser($u) { return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $u); }

function genPass($n=16) {
    $c = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $p = '';
    for ($i=0; $i<$n; $i++) $p .= $c[random_int(0, strlen($c)-1)];
    return $p;
}

function getUUID() {
    return trim(shell_exec('cat /proc/sys/kernel/random/uuid 2>/dev/null') ?? '');
}

// Baca port dari log-install.txt (sama persis autoscript)
function getLogPort($keyword) {
    if (!file_exists(LOG_INSTALL)) return '';
    $lines = file(LOG_INSTALL, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $l) {
        if (strpos($l, $keyword) !== false) {
            $p = explode(':', $l, 2);
            return isset($p[1]) ? trim(str_replace(' ', '', $p[1])) : '';
        }
    }
    return '';
}

// ============================================================
// INJECT XRAY - pakai bash script temp file
// Sama 100% dengan cara autoscript asli bekerja
// ============================================================
function injectVmess($user, $uuid, $exp) {
    $cfg = XRAY_CONFIG;
    $script = <<<BASH
sed -i '/#vmess\$/a\\### {$user} {$exp}\\
},{"id": "{$uuid}","alterId": 0,"email": "{$user}"' {$cfg}
sed -i '/#vmessgrpc\$/a\\### {$user} {$exp}\\
},{"id": "{$uuid}","alterId": 0,"email": "{$user}"' {$cfg}
BASH;
    return runScript($script);
}

function injectVless($user, $uuid, $exp) {
    $cfg = XRAY_CONFIG;
    $script = <<<BASH
sed -i '/#vless\$/a\\#& {$user} {$exp}\\
},{"id": "{$uuid}","email": "{$user}"' {$cfg}
sed -i '/#vlessgrpc\$/a\\#& {$user} {$exp}\\
},{"id": "{$uuid}","email": "{$user}"' {$cfg}
BASH;
    return runScript($script);
}

function injectTrojan($user, $pass, $exp) {
    $cfg = XRAY_CONFIG;
    $script = <<<BASH
sed -i '/#trojanws\$/a\\#! {$user} {$exp}\\
},{"password": "{$pass}","email": "{$user}"' {$cfg}
sed -i '/#trojangrpc\$/a\\#! {$user} {$exp}\\
},{"password": "{$pass}","email": "{$user}"' {$cfg}
BASH;
    return runScript($script);
}

function injectSS($user, $pass, $exp) {
    $cfg = XRAY_CONFIG;
    $script = <<<BASH
sed -i '/#ssws\$/a\\#ss {$user} {$exp}\\
},{"method": "aes-128-gcm","password": "{$pass}","email": "{$user}"' {$cfg}
sed -i '/#ssgrpc\$/a\\#ss {$user} {$exp}\\
},{"method": "aes-128-gcm","password": "{$pass}","email": "{$user}"' {$cfg}
BASH;
    return runScript($script);
}

// Cek apakah user sudah ada di xray config
function userExistsInConfig($user) {
    $c = (int) shell_exec('sudo grep -w "' . $user . '" ' . XRAY_CONFIG . ' 2>/dev/null | wc -l');
    return $c > 0;
}

// Cek status SSH user di shadow
function getSshStatus($user) {
    $s = trim(run("grep \"^{$user}:\" /etc/shadow | cut -d: -f2") ?? '');
    if (!$s || str_starts_with($s, '!') || str_starts_with($s, '*') || $s === '') return 'locked';
    return 'active';
}

// ============================================================
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

switch ($action) {

// ── STATS ────────────────────────────────────────────────────
case 'stats':
    $ssh_count  = (int) run("awk -F: '\$3>=1000 && \$1!=\"nobody\"{print \$1}' /etc/passwd | wc -l");
    $xray_count = (int) shell_exec('sudo grep -c "email" ' . XRAY_CONFIG . ' 2>/dev/null') ?: 0;
    $mt = (int) run("grep MemTotal /proc/meminfo | awk '{print \$2}'");
    $ma = (int) run("grep MemAvailable /proc/meminfo | awk '{print \$2}'");
    $ram = $mt > 0 ? round(($mt-$ma)/1024).'/'.round($mt/1024).'MB' : '0/0MB';
    $up = (int) run("awk '{print int(\$1)}' /proc/uptime");
    $uptime = ($up>=86400 ? intdiv($up,86400).'d ' : '').intdiv($up%86400,3600).'h';
    ok(['ssh_users'=>$ssh_count,'xray_users'=>$xray_count,'ram'=>$ram,'uptime'=>$uptime,'ip'=>getIP()]);

// ── CREATE SSH ───────────────────────────────────────────────
case 'create_ssh':
    $user = trim($input['user'] ?? '');
    $pass = trim($input['pass'] ?? '');
    $exp  = (int)($input['exp'] ?? 30);
    $host = getDomain();
    $ip   = getIP();

    if (!validUser($user)) fail('Username tidak valid (3-20 karakter, huruf/angka/_)');
    if (strlen($pass) < 4)  fail('Password minimal 4 karakter');
    if ($exp < 1 || $exp > 365) fail('Expired harus 1-365 hari');
    if (strpos(run("id {$user}"), 'uid=') !== false) fail("User '{$user}' sudah ada");

    $exp_date = date('Y-m-d', strtotime("+{$exp} days"));

    // Buat user - sama dengan usernew.sh
    // useradd dengan expired date, shell /bin/false, tanpa home dir
    run("useradd -e {$exp_date} -s /bin/false -M {$user}");

    // Set password dengan passwd (BUKAN chpasswd) agar tidak locked
    // Sama dengan: echo -e "$Pass\n$Pass\n" | passwd $Login
    runScript("echo -e \"{$pass}\\n{$pass}\\n\" | passwd {$user}");

    // Verifikasi user berhasil dibuat
    if (strpos(run("id {$user}"), 'uid=') === false) fail('Gagal buat user, cek sudoers');

    // Ambil tanggal expired dari chage (sama dengan autoscript)
    $exp_chage = run("chage -l {$user} 2>/dev/null | grep 'Account expires' | awk -F': ' '{print \$2}'");

    // Baca port dari log-install.txt
    $openssh    = getLogPort('OpenSSH');
    $dropbear   = getLogPort('Dropbear');
    $stunnel    = getLogPort('Stunnel4');
    $sshws      = getLogPort('SSH Websocket');
    $sshws_ssl  = getLogPort('SSH SSL Websocket');

    // Log seperti autoscript
    $log = date('Y-m-d H:i:s')." | user:{$user} | pass:{$pass} | exp:{$exp_date}\n";
    @file_put_contents(SSH_LOG, $log, FILE_APPEND);

    // Output sama persis dengan usernew.sh
    $output  = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "           SSH Account            \n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Username    : {$user}\n";
    $output .= "Password    : {$pass}\n";
    $output .= "Expired On  : {$exp_chage}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "IP          : {$ip}\n";
    $output .= "Host        : {$host}\n";
    $output .= "OpenSSH     : {$openssh}\n";
    $output .= "SSH WS      : {$sshws}\n";
    $output .= "SSH SSL WS  : {$sshws_ssl}\n";
    $output .= "SSL/TLS     : {$stunnel}\n";
    $output .= "UDPGW       : 7100-7400\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Payload WSS\n\n";
    $output .= "GET wss://isi_bug_disini HTTP/1.1[crlf]Host: {$host}[crlf]Upgrade: websocket[crlf][crlf]\n\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Payload WS\n\n";
    $output .= "GET / HTTP/1.1[crlf]Host: {$host}[crlf]Upgrade: websocket[crlf][crlf]\n\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";

    ok([
        'user'      => $user,
        'pass'      => $pass,
        'exp_date'  => $exp_chage,
        'host'      => $host,
        'ip'        => $ip,
        'openssh'   => $openssh ?: '22',
        'dropbear'  => $dropbear ?: '109, 143',
        'stunnel'   => $stunnel ?: '222, 777',
        'sshws'     => $sshws ?: '80',
        'sshws_ssl' => $sshws_ssl ?: '443',
        'udpgw'     => '7100-7400',
        'output'    => $output,
    ], 'SSH account created');

// ── CREATE VMESS ─────────────────────────────────────────────
case 'create_vmess':
    $user = trim($input['user'] ?? '');
    $exp  = (int)($input['exp'] ?? 30);
    $host = getDomain();

    if (!validUser($user))  fail('Username tidak valid');
    if ($exp < 1 || $exp > 365) fail('Expired harus 1-365 hari');
    if (!file_exists(XRAY_CONFIG)) fail('Xray config tidak ditemukan');
    if (userExistsInConfig($user)) fail("User '{$user}' sudah ada di xray config");

    $uuid = getUUID();
    if (!$uuid) fail('Gagal generate UUID');
    $exp_date = date('Y-m-d', strtotime("+{$exp} days"));

    // Cek marker
    $has_marker = (int) shell_exec('sudo grep -c "#vmess$" ' . XRAY_CONFIG . ' 2>/dev/null');
    if ($has_marker < 1) fail('Marker #vmess tidak ditemukan di config. Pastikan autoscript terinstall dengan benar.');

    // Inject via bash script (100% sama dengan add-ws.sh)
    injectVmess($user, $uuid, $exp_date);

    // Restart xray
    run('systemctl restart xray');
    sleep(1);
    if (run('systemctl is-active xray') !== 'active') fail('User ditambahkan tapi xray gagal restart');

    // Baca port dari log-install.txt (sama dengan autoscript)
    $port_tls  = getLogPort('Vmess WS TLS') ?: '443';
    $port_none = getLogPort('Vmess WS none TLS') ?: '80';

    // Generate link (sama persis dengan add-ws.sh)
    $tls_obj  = ['v'=>'2','ps'=>$user,'add'=>$host,'port'=>$port_tls, 'id'=>$uuid,'aid'=>'0','net'=>'ws', 'path'=>'/vmess','type'=>'none','host'=>'','tls'=>'tls'];
    $http_obj = ['v'=>'2','ps'=>$user,'add'=>$host,'port'=>$port_none,'id'=>$uuid,'aid'=>'0','net'=>'ws', 'path'=>'/vmess','type'=>'none','host'=>'','tls'=>'none'];
    $grpc_obj = ['v'=>'2','ps'=>$user,'add'=>$host,'port'=>$port_tls, 'id'=>$uuid,'aid'=>'0','net'=>'grpc','path'=>'vmess-grpc','type'=>'none','host'=>'','tls'=>'tls'];

    $link_tls  = 'vmess://' . base64_encode(json_encode($tls_obj));
    $link_http = 'vmess://' . base64_encode(json_encode($http_obj));
    $link_grpc = 'vmess://' . base64_encode(json_encode($grpc_obj));

    @file_put_contents(VMESS_LOG, date('Y-m-d H:i:s')." | user:{$user} | uuid:{$uuid} | exp:{$exp_date}\n", FILE_APPEND);

    // Output sama persis dengan add-ws.sh
    $output  = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "        Vmess Account        \n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Remarks        : {$user}\n";
    $output .= "Domain         : {$host}\n";
    $output .= "Wildcard       : (bug.com).{$host}\n";
    $output .= "Port TLS       : {$port_tls}\n";
    $output .= "Port none TLS  : {$port_none}\n";
    $output .= "Port gRPC      : {$port_tls}\n";
    $output .= "id             : {$uuid}\n";
    $output .= "alterId        : 0\n";
    $output .= "Security       : auto\n";
    $output .= "Network        : ws\n";
    $output .= "Path           : /vmess\n";
    $output .= "ServiceName    : vmess-grpc\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Link TLS       : {$link_tls}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Link none TLS  : {$link_http}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Link gRPC      : {$link_grpc}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Expired On     : {$exp_date}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";

    ok([
        'user'        => $user,
        'uuid'        => $uuid,
        'host'        => $host,
        'exp_date'    => $exp_date,
        'port_tls'    => $port_tls,
        'port_http'   => $port_none,
        'path'        => '/vmess',
        'grpc_service'=> 'vmess-grpc',
        'link_tls'    => $link_tls,
        'link_http'   => $link_http,
        'link_grpc'   => $link_grpc,
        'output'      => $output,
    ], 'Vmess account created');

// ── CREATE VLESS ─────────────────────────────────────────────
case 'create_vless':
    $user = trim($input['user'] ?? '');
    $exp  = (int)($input['exp'] ?? 30);
    $host = getDomain();

    if (!validUser($user))  fail('Username tidak valid');
    if ($exp < 1 || $exp > 365) fail('Expired harus 1-365 hari');
    if (!file_exists(XRAY_CONFIG)) fail('Xray config tidak ditemukan');
    if (userExistsInConfig($user)) fail("User '{$user}' sudah ada di xray config");

    $uuid = getUUID();
    if (!$uuid) fail('Gagal generate UUID');
    $exp_date = date('Y-m-d', strtotime("+{$exp} days"));

    $has_marker = (int) shell_exec('sudo grep -c "#vless$" ' . XRAY_CONFIG . ' 2>/dev/null');
    if ($has_marker < 1) fail('Marker #vless tidak ditemukan di config');

    injectVless($user, $uuid, $exp_date);
    run('systemctl restart xray');
    sleep(1);
    if (run('systemctl is-active xray') !== 'active') fail('User ditambahkan tapi xray gagal restart');

    $port_tls  = getLogPort('Vless WS TLS') ?: '443';
    $port_none = getLogPort('Vless WS none TLS') ?: '80';

    // Link sama persis dengan add-vless.sh
    $link_tls  = "vless://{$uuid}@{$host}:{$port_tls}?path=%2Fvless&security=tls&encryption=none&type=ws#{$user}";
    $link_http = "vless://{$uuid}@{$host}:{$port_none}?path=%2Fvless&encryption=none&type=ws#{$user}";
    $link_grpc = "vless://{$uuid}@{$host}:{$port_tls}?mode=gun&security=tls&encryption=none&type=grpc&serviceName=vless-grpc&sni=bug.com#{$user}";

    @file_put_contents(VLESS_LOG, date('Y-m-d H:i:s')." | user:{$user} | uuid:{$uuid} | exp:{$exp_date}\n", FILE_APPEND);

    $output  = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "        Vless Account        \n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Remarks        : {$user}\n";
    $output .= "Domain         : {$host}\n";
    $output .= "Wildcard       : (bug.com).{$host}\n";
    $output .= "Port TLS       : {$port_tls}\n";
    $output .= "Port none TLS  : {$port_none}\n";
    $output .= "id             : {$uuid}\n";
    $output .= "Encryption     : none\n";
    $output .= "Network        : ws\n";
    $output .= "Path           : /vless\n";
    $output .= "Path gRPC      : vless-grpc\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Link TLS       : {$link_tls}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Link none TLS  : {$link_http}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Link gRPC      : {$link_grpc}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Expired On     : {$exp_date}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";

    ok([
        'user'        => $user,
        'uuid'        => $uuid,
        'host'        => $host,
        'exp_date'    => $exp_date,
        'port_tls'    => $port_tls,
        'port_http'   => $port_none,
        'path'        => '/vless',
        'grpc_service'=> 'vless-grpc',
        'link_tls'    => $link_tls,
        'link_http'   => $link_http,
        'link_grpc'   => $link_grpc,
        'output'      => $output,
    ], 'Vless account created');

// ── CREATE TROJAN ─────────────────────────────────────────────
case 'create_trojan':
    $user = trim($input['user'] ?? '');
    $exp  = (int)($input['exp'] ?? 30);
    $host = getDomain();

    if (!validUser($user))  fail('Username tidak valid');
    if ($exp < 1 || $exp > 365) fail('Expired harus 1-365 hari');
    if (!file_exists(XRAY_CONFIG)) fail('Xray config tidak ditemukan');
    if (userExistsInConfig($user)) fail("User '{$user}' sudah ada di xray config");

    $pass = getUUID();
    if (!$pass) fail('Gagal generate password');
    $exp_date = date('Y-m-d', strtotime("+{$exp} days"));

    $has_marker = (int) shell_exec('sudo grep -c "#trojanws$" ' . XRAY_CONFIG . ' 2>/dev/null');
    if ($has_marker < 1) fail('Marker #trojanws tidak ditemukan di config');

    injectTrojan($user, $pass, $exp_date);
    run('systemctl restart xray');
    sleep(1);
    if (run('systemctl is-active xray') !== 'active') fail('User ditambahkan tapi xray gagal restart');

    $port_tls  = getLogPort('Trojan WS TLS') ?: '443';
    $port_none = getLogPort('Trojan WS none TLS') ?: '80';

    // Link sama persis dengan add-tr.sh
    $link_tls  = "trojan://{$pass}@{$host}:{$port_tls}?path=%2Ftrojan-ws&security=tls&host={$host}&type=ws&sni={$host}#{$user}";
    $link_http = "trojan://{$pass}@{$host}:{$port_none}?path=%2Ftrojan-ws&security=none&host={$host}&type=ws#{$user}";
    $link_grpc = "trojan://{$pass}@{$host}:{$port_tls}?mode=gun&security=tls&type=grpc&serviceName=trojan-grpc&sni={$host}#{$user}";

    @file_put_contents(TROJAN_LOG, date('Y-m-d H:i:s')." | user:{$user} | pass:{$pass} | exp:{$exp_date}\n", FILE_APPEND);

    $output  = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "           TROJAN ACCOUNT           \n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Remarks        : {$user}\n";
    $output .= "Host/IP        : {$host}\n";
    $output .= "Wildcard       : (bug.com).{$host}\n";
    $output .= "Port TLS       : {$port_tls}\n";
    $output .= "Port none TLS  : {$port_none}\n";
    $output .= "Port gRPC      : {$port_tls}\n";
    $output .= "Key            : {$pass}\n";
    $output .= "Path           : /trojan-ws\n";
    $output .= "ServiceName    : trojan-grpc\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Link TLS       : {$link_tls}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Link none TLS  : {$link_http}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Link gRPC      : {$link_grpc}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Expired On     : {$exp_date}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";

    ok([
        'user'        => $user,
        'pass'        => $pass,
        'host'        => $host,
        'exp_date'    => $exp_date,
        'port_tls'    => $port_tls,
        'port_http'   => $port_none,
        'path'        => '/trojan-ws',
        'grpc_service'=> 'trojan-grpc',
        'link_tls'    => $link_tls,
        'link_http'   => $link_http,
        'link_grpc'   => $link_grpc,
        'output'      => $output,
    ], 'Trojan account created');

// ── CREATE SHADOWSOCKS ────────────────────────────────────────
case 'create_ss':
    $user = trim($input['user'] ?? '');
    $exp  = (int)($input['exp'] ?? 30);
    $host = getDomain();

    if (!validUser($user))  fail('Username tidak valid');
    if ($exp < 1 || $exp > 365) fail('Expired harus 1-365 hari');
    if (!file_exists(XRAY_CONFIG)) fail('Xray config tidak ditemukan');
    if (userExistsInConfig($user)) fail("User '{$user}' sudah ada di xray config");

    $pass     = genPass(16);
    $exp_date = date('Y-m-d', strtotime("+{$exp} days"));

    $has_marker = (int) shell_exec('sudo grep -c "#ssws$" ' . XRAY_CONFIG . ' 2>/dev/null');
    if ($has_marker < 1) fail('Marker #ssws tidak ditemukan di config');

    injectSS($user, $pass, $exp_date);
    run('systemctl restart xray');
    sleep(1);
    if (run('systemctl is-active xray') !== 'active') fail('User ditambahkan tapi xray gagal restart');

    $port_tls  = getLogPort('SS WS TLS') ?: '443';
    $port_none = getLogPort('SS WS none TLS') ?: '80';

    $ss_b64    = base64_encode("aes-128-gcm:{$pass}");
    $link_tls  = "ss://{$ss_b64}@{$host}:{$port_tls}?plugin=v2ray-plugin%3Btls%3Bmode%3Dwebsocket%3Bpath%3D%2Fss-ws%3Bhost%3D{$host}#{$user}";
    $link_http = "ss://{$ss_b64}@{$host}:{$port_none}?plugin=v2ray-plugin%3Bmode%3Dwebsocket%3Bpath%3D%2Fss-ws%3Bhost%3D{$host}#{$user}";

    @file_put_contents(SS_LOG, date('Y-m-d H:i:s')." | user:{$user} | pass:{$pass} | exp:{$exp_date}\n", FILE_APPEND);

    $output  = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "      Shadowsocks Account      \n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Remarks        : {$user}\n";
    $output .= "Host           : {$host}\n";
    $output .= "Port TLS       : {$port_tls}\n";
    $output .= "Port none TLS  : {$port_none}\n";
    $output .= "Password       : {$pass}\n";
    $output .= "Method         : aes-128-gcm\n";
    $output .= "Path           : /ss-ws\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Link TLS       : {$link_tls}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Link none TLS  : {$link_http}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Expired On     : {$exp_date}\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";

    ok([
        'user'      => $user,
        'pass'      => $pass,
        'method'    => 'aes-128-gcm',
        'host'      => $host,
        'exp_date'  => $exp_date,
        'port_tls'  => $port_tls,
        'port_http' => $port_none,
        'link_tls'  => $link_tls,
        'link_http' => $link_http,
        'output'    => $output,
    ], 'Shadowsocks account created');

// ── LIST USERS ────────────────────────────────────────────────
case 'list_users':
    $ssh = [];
    $raw_users = run("awk -F: '\$3>=1000&&\$1!=\"nobody\"&&\$1!=\"ubuntu\"&&\$1!=\"www-data\"{print \$1}' /etc/passwd");
    foreach (explode("\n", $raw_users) as $u) {
        $u = trim($u);
        if (!$u) continue;
        $exp    = run("chage -l {$u} 2>/dev/null | grep 'Account expires' | awk -F': ' '{print \$2}'");
        $ssh[]  = ['user'=>$u, 'exp'=>trim($exp)?:'never', 'status'=>getSshStatus($u)];
    }

    // Parse xray users dari config
    $xray = [];
    if (file_exists(XRAY_CONFIG)) {
        $cfg_raw = shell_exec('sudo cat ' . XRAY_CONFIG . ' 2>/dev/null') ?? '';
        // Match email field: "email": "username" (tanpa @) atau dengan @proto
        preg_match_all('/"email":\s*"([^"@]+)(?:@([^"]*))?"/', $cfg_raw, $m);
        $seen = [];
        for ($i = 0; $i < count($m[1]); $i++) {
            $uname = $m[1][$i];
            $proto = strtoupper($m[2][$i] ?? 'unknown');
            $key   = $uname . '_' . $proto;
            if (!isset($seen[$key])) {
                $seen[$key] = 1;
                // Cari tanggal expired dari comment di atas entry
                $expDate = '';
                if (preg_match('/(?:###|#&|#!|#ss)\s+' . preg_quote($uname, '/') . '\s+([\d-]+)/', $cfg_raw, $dm)) {
                    $expDate = $dm[1];
                }
                $xray[] = ['user'=>$uname, 'type'=>$proto, 'exp'=>$expDate?:'-'];
            }
        }
    }
    ok(['ssh'=>$ssh, 'xray'=>$xray]);

// ── DELETE SSH ────────────────────────────────────────────────
case 'delete_ssh':
    $user = trim($input['user'] ?? '');
    if (!validUser($user)) fail('Username tidak valid');
    if (strpos(run("id {$user}"), 'uid=') === false) fail("User tidak ditemukan");
    run("pkill -u {$user}");
    run("userdel -f {$user}");
    ok([], "User SSH '{$user}' dihapus");

// ── DELETE XRAY ───────────────────────────────────────────────
case 'delete_xray':
    $user = trim($input['user'] ?? '');
    if (!validUser($user)) fail('Username tidak valid');
    if (!file_exists(XRAY_CONFIG)) fail('Config tidak ditemukan');
    // Hapus entry user dari config (comment + json entry)
    $script = 'sed -i "/\\b' . $user . '\\b/d" ' . XRAY_CONFIG;
    run($script);
    run('systemctl restart xray');
    sleep(1);
    ok([], "User xray '{$user}' dihapus");

// ── SERVICE STATUS ────────────────────────────────────────────
case 'service_status':
    $svcs  = ['xray','nginx','ssh','dropbear','stunnel4','ws-dropbear','ws-stunnel','fail2ban','cron'];
    $names = ['Xray','Nginx','OpenSSH','Dropbear','Stunnel4','WS-Dropbear','WS-Stunnel','Fail2Ban','Cron'];
    $res = [];
    foreach ($svcs as $i => $s) {
        $res[] = ['name'=>$names[$i], 'svc'=>$s, 'status'=>run("systemctl is-active {$s}")];
    }
    ok($res);

// ── RESTART SERVICE ───────────────────────────────────────────
case 'restart_service':
    $name    = $input['name'] ?? '';
    $allowed = ['xray','nginx','ssh','dropbear','stunnel4','ws-dropbear','ws-stunnel','fail2ban','cron'];
    if (!in_array($name, $allowed)) fail('Service tidak diizinkan');
    run("systemctl restart {$name}");
    $st = run("systemctl is-active {$name}");
    if ($st === 'active') ok([], "{$name} berhasil direstart");
    else fail("{$name} gagal restart: {$st}");

// ── DEBUG ─────────────────────────────────────────────────────
case 'debug':
    $markers = ['#vmess','#vless','#trojanws','#ssws','#vmessgrpc','#vlessgrpc','#trojangrpc','#ssgrpc'];
    $found = [];
    foreach ($markers as $m) {
        $pat = substr($m, 1) . '$';
        $c   = (int) shell_exec('sudo grep -c "' . $pat . '" ' . XRAY_CONFIG . ' 2>/dev/null');
        $found[$m] = $c > 0;
    }
    ok([
        'xray_status'   => run('systemctl is-active xray'),
        'config_exists' => file_exists(XRAY_CONFIG),
        'config_size'   => file_exists(XRAY_CONFIG) ? filesize(XRAY_CONFIG).'B' : '0',
        'markers'       => $found,
        'domain'        => getDomain(),
        'php_user'      => run('whoami'),
        'sudo_test'     => run('sudo whoami'),
    ]);

default:
    fail('Action tidak dikenal: ' . $action);
}
