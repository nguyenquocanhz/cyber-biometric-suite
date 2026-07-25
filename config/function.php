<?php
if (!isset($CMSNT)) {
    $CMSNT = new CMSNT;
}

// Get credentials from environment variables
// Get credentials from database with environment/hardcoded fallback
try {
    $client_key_cf = $CMSNT->site('turnstile_site_key') ?: ($_ENV['TURNSTILE_SITE_KEY'] ?? "0x4AAAAAABHTj-O0ZB-5Ejdh");
    $secrec_key_cf = $CMSNT->site('turnstile_secret_key') ?: ($_ENV['TURNSTILE_SECRET_KEY'] ?? "0x4AAAAAABHTj79I2zthQzkucAziT2ZZqpo");
} catch (Throwable $e) {
    $client_key_cf = $_ENV['TURNSTILE_SITE_KEY'] ?? "0x4AAAAAABHTj-O0ZB-5Ejdh";
    $secrec_key_cf = $_ENV['TURNSTILE_SECRET_KEY'] ?? "0x4AAAAAABHTj79I2zthQzkucAziT2ZZqpo";
}
global $base_url;
$config = [
    'url' => $base_url,
    'serial' => '26072021',
    'version' => '1.2.7',
    'ip_server' => ''
];


function is_proxy()
{
    global $CMSNT;
    try {
        if (isset($CMSNT) && $CMSNT->site('enable_proxy_block') == 0) {
            return false;
        }
    } catch (Throwable $e) {}

    // Bỏ qua nếu đi qua Cloudflare
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return false;
    }

    $proxy_headers = [
        'HTTP_VIA',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED',
        'HTTP_CLIENT_IP',
        'HTTP_FORWARDED_FOR_IP',
        'VIA',
        'X_FORWARDED_FOR',
        'FORWARDED_FOR',
        'X_FORWARDED',
        'FORWARDED',
        'CLIENT_IP',
        'FORWARDED_FOR_IP',
        'HTTP_PROXY_CONNECTION'
    ];
    foreach ($proxy_headers as $header) {
        if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
            return true;
        }
    }
    return false;
}



function BlockedIPs($ip, $blockedIPs)
{
    if (in_array($ip, $blockedIPs)) {
        header('HTTP/1.0 403 Forbidden');
        echo 'Access Denied.';
        exit;
    }
}


function getMoney_momo($token)
{
    $url = "https://api.web2m.com/apigetsodu/$token";
    $result = json_decode(curl_get($url), true);
    return isset($result['status']) && $result['status'] == 200 ? $result['SoDu'] : 0;
}

function insert_options($name, $value)
{
    global $CMSNT;
    if (!$CMSNT->get_row("SELECT * FROM `options` WHERE `name` = '$name' ")) {
        $CMSNT->insert("options", [
            'name' => $name,
            'value' => $value
        ]);
    }
}
function getSite($name)
{
    global $CMSNT;
    $row = $CMSNT->get_row("SELECT * FROM `options` WHERE `name` = '$name' ");
    return $row['value'] ?? '';
}
function getUser($username, $row)
{
    global $CMSNT;
    $user = $CMSNT->get_row("SELECT * FROM `users` WHERE `username` = '$username' ");
    return $user[$row] ?? '';
}
function verify_turnstile($token, $secret, $remoteip = null)
{
    if (empty($token) || empty($secret)) {
        return ['success' => false, 'error' => 'Missing token or secret'];
    }

    $url = "https://challenges.cloudflare.com/turnstile/v0/siteverify";
    $data = http_build_query([
        'secret' => $secret,
        'response' => $token,
        'remoteip' => $remoteip
    ]);

    // Sử dụng cURL để POST
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // optional: set SSL_VERIFY if needed (default is fine)
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return ['success' => false, 'error' => 'cURL error: ' . $err];
    }

    $json = json_decode($resp, true);
    if (!is_array($json)) {
        return ['success' => false, 'error' => 'Invalid response from Turnstile'];
    }

    // Turnstile trả về key 'success' (boolean) và có thể có 'challenge_ts', 'hostname', 'error-codes'
    return $json;
}

function verify_recaptcha_v2($token, $secret, $remoteip = null)
{
    if (empty($token) || empty($secret)) {
        return ['success' => false, 'error' => 'Missing token or secret'];
    }

    $url = 'https://www.google.com/recaptcha/api/siteverify';

    $data = http_build_query([
        'secret' => $secret,
        'response' => $token,
        'remoteip' => $remoteip
    ]);

    // Use cURL to POST
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return ['success' => false, 'error' => 'cURL error: ' . $err];
    }

    $json = json_decode($resp, true);
    if (!is_array($json)) {
        return ['success' => false, 'error' => 'Invalid response from reCAPTCHA'];
    }

    // reCAPTCHA returns 'success' (boolean) and may have 'error-codes'
    return $json;
}

function admin_log($content)
{
    global $CMSNT;
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; // Assuming session has user_id
    // If Admin session uses 'username', fetching ID might be needed if user_id not set.
    // admin/index.php uses $_SESSION['admin'] usually or check_login_admin() sets session.
    // Let's assume user_id is in session or query it.
    // Most CMSNT systems store 'username' in session.
    if ($user_id == 0 && isset($_SESSION['username'])) {
        $user = $CMSNT->get_row("SELECT id FROM users WHERE username = '" . $_SESSION['username'] . "'");
        $user_id = $user ? $user['id'] : 0;
    }

    $ip = myip();
    $CMSNT->insert("logs", [
        'user_id' => $user_id,
        'content' => $content,
        'createdate' => gettime(),
        'ip' => $ip
    ]);
}
function format_date($time)
{
    return date("H:i:s d/m/Y", $time);
}
/* CONFIG RÚT TIỀN */
function listbank()
{
    $html = '
    <option value="">Chọn ngân hàng</option>
    <option value="MOMO">MOMO</option>
    <option value="VIETTEL PAY">VIETTEL PAY</option>
    <option value="ZALO PAY">ZALO PAY</option>
    <option value="AIRPAY">AIRPAY</option>
    <option value="VIETINBANK">VIETINBANK</option>
    <option value="VIETCOMBANK">VIETCOMBANK</option>
    <option value="AGRIBANK">AGRIBANK</option>
    <option value="TPBANK">TPBANK</option>
    <option value="HDB">HDB</option>
    <option value="VPBANK">VPBANK</option>
    <option value="MBBANK">MBBANK</option>
    <option value="OCEANBANK">OCEANBANK</option>
    <option value="BIDV">BIDV</option>
    <option value="SACOMBANK">SACOMBANK</option>
    <option value="ACB">ACB</option>
    <option value="ABBANK">ABBANK</option>
    <option value="NCB">NCB</option>
    <option value="IBK">IBK</option>
    <option value="CIMB">CIMB</option>
    <option value="EXIMBANK">EXIMBANK</option>
    <option value="SEABANK">SEABANK</option>
    <option value="SCB">SCB</option>
    <option value="DONGABANK">DONGABANK</option>
    <option value="SAIGONBANK">SAIGONBANK</option>
    <option value="PG BANK">PG BANK</option>
    <option value="PVCOMBANK">PVCOMBANK</option>
    <option value="KIENLONGBANK">KIENLONGBANK</option>
    <option value="VIETCAPITAL BANK">VIETCAPITAL BANK</option>
    <option value="OCB">OCB</option>
    <option value="MSB">MSB</option>
    <option value="SHB">SHB</option>
    <option value="VIETBANK">VIETBANK</option>
    <option value="VRB">VRB</option>
    <option value="NAMABANK">NAMABANK</option>
    <option value="SHBVN">SHBVN</option>
    <option value="VIB">VIB</option>
    <option value="TECHCOMBANK">TECHCOMBANK</option>
    ';
    return $html;
}

function daily($data)
{
    if ($data == 0) {
        return 'Thành viên';
    } else if ($data == 1) {
        return 'Đại lý';
    }
}
function trangthai($data)
{
    if ($data == 'xuly') {
        return 'Đang xử lý';
    } else if ($data == 'hoantat') {
        return 'Hoàn tất';
    } else if ($data == 'thanhcong') {
        return 'Thành công';
    } else if ($data == 'huy') {
        return 'Hủy';
    } else if ($data == 'thatbai') {
        return 'Thất bại';
    } else {
        return 'Khác';
    }
}

function parse_order_id(string $des): ?int
{
    global $CMSNT;
    $prefix = $CMSNT->site('noidung_naptien');
    $orderId = null;
    if (str_starts_with($des, $prefix)) {
        $orderId = (int) substr($des, strlen($prefix));
    }
    return $orderId;
}

function BASE_URL($url = '')
{
    global $base_url;
    return $base_url . $url;
}
function gettime()
{
    return date('Y-m-d H:i:s', time());
}
function check_string($data)
{
    if ($data === null) return '';
    return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
}

/**
 * Create URL-friendly slug from text
 * Supports Vietnamese characters
 */
function create_slug($text)
{
    // Convert Vietnamese characters to non-accented equivalents
    $text = mb_strtolower($text, 'UTF-8');

    $vietnamese = array(
        'á',
        'à',
        'ả',
        'ã',
        'ạ',
        'ă',
        'ắ',
        'ằ',
        'ẳ',
        'ẵ',
        'ặ',
        'â',
        'ấ',
        'ầ',
        'ẩ',
        'ẫ',
        'ậ',
        'é',
        'è',
        'ẻ',
        'ẽ',
        'ẹ',
        'ê',
        'ế',
        'ề',
        'ể',
        'ễ',
        'ệ',
        'í',
        'ì',
        'ỉ',
        'ĩ',
        'ị',
        'ó',
        'ò',
        'ỏ',
        'õ',
        'ọ',
        'ô',
        'ố',
        'ồ',
        'ổ',
        'ỗ',
        'ộ',
        'ơ',
        'ớ',
        'ờ',
        'ở',
        'ỡ',
        'ợ',
        'ú',
        'ù',
        'ủ',
        'ũ',
        'ụ',
        'ư',
        'ứ',
        'ừ',
        'ử',
        'ữ',
        'ự',
        'ý',
        'ỳ',
        'ỷ',
        'ỹ',
        'ỵ',
        'đ'
    );

    $latin = array(
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'e',
        'e',
        'e',
        'e',
        'e',
        'e',
        'e',
        'e',
        'e',
        'e',
        'e',
        'i',
        'i',
        'i',
        'i',
        'i',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'u',
        'u',
        'u',
        'u',
        'u',
        'u',
        'u',
        'u',
        'u',
        'u',
        'u',
        'y',
        'y',
        'y',
        'y',
        'y',
        'd'
    );

    $text = str_replace($vietnamese, $latin, $text);

    // Remove special characters and replace spaces with hyphens
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');

    return $text;
}

function format_cash($price)
{
    return str_replace(",", ".", number_format($price));
}
function curl_get($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);

    curl_close($ch);
    return $data;
}
function random($string, $int)
{
    return substr(str_shuffle($string), 0, $int);
}
function pheptru($int1, $int2)
{
    return $int1 - $int2;
}
function phepcong($int1, $int2)
{
    return $int1 + $int2;
}
function phepnhan($int1, $int2)
{
    return $int1 * $int2;
}
function phepchia($int1, $int2)
{
    return $int1 / $int2;
}
function check_img($img)
{
    $filename = $_FILES[$img]['name'];
    $ext = explode(".", $filename);
    $ext = end($ext);
    $valid_ext = array("png", "jpeg", "jpg", "PNG", "JPEG", "JPG", "gif", "GIF");
    if (in_array($ext, $valid_ext)) {
        return true;
    }
}
function msg_success2($text)
{
    return die('<div class="alert alert-success alert-dismissible error-messages">
    <a href="#" class="close" data-dismiss="alert" aria-badge="close">×</a>' . $text . '</div>');
}
function msg_error2($text)
{
    return die('<div class="alert alert-danger alert-dismissible error-messages">
    <a href="#" class="close" data-dismiss="alert" aria-badge="close">×</a>' . $text . '</div>');
}
function msg_warning2($text)
{
    return die('<div class="alert alert-warning alert-dismissible error-messages">
    <a href="#" class="close" data-dismiss="alert" aria-badge="close">×</a>' . $text . '</div>');
}
function msg_success($text, $url, $time)
{
    return die('<div class="alert alert-success alert-dismissible error-messages">
    <a href="#" class="close" data-dismiss="alert" aria-badge="close">×</a>' . $text . '</div><script type="text/javascript">setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function msg_error($text, $url, $time)
{
    return die('<div class="alert alert-danger alert-dismissible error-messages">
    <a href="#" class="close" data-dismiss="alert" aria-badge="close">×</a>' . $text . '</div><script type="text/javascript">setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function msg_warning($text, $url, $time)
{
    return die('<div class="alert alert-warning alert-dismissible error-messages">
    <a href="#" class="close" data-dismiss="alert" aria-badge="close">×</a>' . $text . '</div><script type="text/javascript">setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function admin_msg_success($text, $url, $time)
{
    return die('<script type="text/javascript">Swal.fire("Thành Công", "' . $text . '","success");
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function admin_msg_error($text, $url, $time)
{
    return die('<script type="text/javascript">Swal.fire("Thất Bại", "' . $text . '","error");
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function admin_msg_warning($text, $url, $time)
{
    return die('<script type="text/javascript">Swal.fire("Thông Báo", "' . $text . '","warning");
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function display_banned($data)
{
    if ($data == 1) {
        $show = '<span class="badge badge-danger">Banned</span>';
    } else if ($data == 0) {
        $show = '<span class="badge badge-success">Hoạt động</span>';
    }
    return $show;
}
function display_loaithe($data)
{
    if ($data == 0) {
        $show = '<span class="label label-warning">Bảo trì</span>';
    } else {
        $show = '<span class="label label-success">Hoạt động</span>';
    }
    return $show;
}
function display_ruttien($data)
{
    if ($data == 'xuly') {
        $show = '<span class="badge badge-info">Đang xử lý</span>';
    } else if ($data == 'hoantat') {
        $show = '<span class="badge badge-success">Đã thanh toán</span>';
    } else if ($data == 'huy') {
        $show = '<span class="badge badge-danger">Hủy</span>';
    }
    return $show;
}
function display_ruttien_user($data)
{
    if ($data == 'xuly') {
        $show = '<span class="label label-info">Đang xử lý</span>';
    } else if ($data == 'hoantat') {
        $show = '<span class="label label-success">Đã thanh toán</span>';
    } else if ($data == 'huy') {
        $show = '<span class="label label-danger">Hủy</span>';
    }
    return $show;
}


function XoaDauCach($text)
{
    return trim(preg_replace('/\s+/', ' ', $text));
}
function display($data)
{
    if ($data == 'HIDE') {
        $show = '<span class="badge badge-danger">ẨN</span>';
    } else if ($data == 'SHOW') {
        $show = '<span class="badge badge-success">HIỂN THỊ</span>';
    }
    return $show;
}
function status($data)
{
    if ($data == 'xuly') {
        $show = '<span class="label label-info">Đang xử lý</span>';
    } else if ($data == 'hoantat') {
        $show = '<span class="label label-success">Hoàn tất</span>';
    } else if ($data == 'thanhcong') {
        $show = '<span class="label label-success">Thành công</span>';
    } else if ($data == 'success') {
        $show = '<span class="label label-success">Success</span>';
    } else if ($data == 'thatbai') {
        $show = '<span class="label label-danger">Thất bại</span>';
    } else if ($data == 'error') {
        $show = '<span class="label label-danger">Error</span>';
    } else if ($data == 'loi') {
        $show = '<span class="label label-danger">Lỗi</span>';
    } else if ($data == 'huy') {
        $show = '<span class="label label-danger">Hủy</span>';
    } else if ($data == 'dangnap') {
        $show = '<span class="label label-warning">Đang đợi nạp</span>';
    } else if ($data == 2) {
        $show = '<span class="label label-success">Hoàn tất</span>';
    } else if ($data == 1) {
        $show = '<span class="label label-info">Đang xử lý</span>';
    } else {
        $show = '<span class="label label-warning">Khác</span>';
    }
    return $show;
}
function status_admin($data)
{
    if ($data == 'xuly') {
        $show = '<span class="badge badge-info">Đang xử lý</span>';
    } else if ($data == 'hoantat') {
        $show = '<span class="badge badge-success">Hoàn tất</span>';
    } else if ($data == 'thanhcong') {
        $show = '<span class="badge badge-success">Thành công</span>';
    } else if ($data == 'success') {
        $show = '<span class="badge badge-success">Success</span>';
    } else if ($data == 'thatbai') {
        $show = '<span class="badge badge-danger">Thất bại</span>';
    } else if ($data == 'error') {
        $show = '<span class="badge badge-danger">Error</span>';
    } else if ($data == 'loi') {
        $show = '<span class="badge badge-danger">Lỗi</span>';
    } else if ($data == 'huy') {
        $show = '<span class="badge badge-danger">Hủy</span>';
    } else if ($data == 'dangnap') {
        $show = '<span class="badge badge-warning">Đang đợi nạp</span>';
    } else if ($data == 2) {
        $show = '<span class="badge badge-success">Hoàn tất</span>';
    } else if ($data == 1) {
        $show = '<span class="badge badge-info">Đang xử lý</span>';
    } else {
        $show = '<span class="badge badge-warning">Khác</span>';
    }
    return $show;
}
function GetBaseURL()
{
    $domain = $_SERVER['HTTP_HOST'];
    return $domain;
}
function check_username($data)
{
    if (preg_match('/^[a-zA-Z0-9_-]{3,16}$/', $data, $matches)) {
        return True;
    } else {
        return False;
    }
}
function check_email($data)
{
    if (preg_match('/^.+@.+$/', $data, $matches)) {
        return True;
    } else {
        return False;
    }
}
function check_phone($data)
{
    if (preg_match('/^\+?(\d.*){3,}$/', $data, $matches)) {
        return True;
    } else {
        return False;
    }
}
function check_url($url)
{
    $c = curl_init();
    curl_setopt($c, CURLOPT_URL, $url);
    curl_setopt($c, CURLOPT_HEADER, 1);
    curl_setopt($c, CURLOPT_NOBODY, 1);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FRESH_CONNECT, 1);
    if (!curl_exec($c)) {
        return false;
    } else {
        return true;
    }
}
function check_zip($img)
{
    $filename = $_FILES[$img]['name'];
    $ext = explode(".", $filename);
    $ext = end($ext);
    $valid_ext = array("zip", "ZIP");
    if (in_array($ext, $valid_ext)) {
        return true;
    }
}
function TypePassword($string)
{
    // Using password_hash for better security (bcrypt)
    return password_hash($string, PASSWORD_BCRYPT);
}
function MD5_Password($password)
{
    return md5($password);
}
function VerifyPassword($password, $hash)
{
    // For legacy md5 hashes, check both methods
    if (strlen($hash) === 32 && ctype_xdigit($hash)) {
        // Old MD5 hash
        return md5($password) === $hash;
    }
    // New bcrypt hash
    return password_verify($password, $hash);
}
function phantrang($url, $start, $total, $kmess)
{
    $out[] = '<nav aria-badge="Page navigation example"><ul class="pagination pagination-lg">';
    $neighbors = 2;
    if ($start >= $total)
        $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
    else
        $start = max(0, (int) $start - ((int) $start % (int) $kmess));
    $base_link = '<li class="page-item"><a class="page-link" href="' . strtr($url, array('%' => '%%')) . 'page=%d' . '">%s</a></li>';
    $out[] = $start == 0 ? '' : sprintf($base_link, $start / $kmess, '<i class="fas fa-angle-left"></i>');
    if ($start > $kmess * $neighbors)
        $out[] = sprintf($base_link, 1, '1');
    if ($start > $kmess * ($neighbors + 1))
        $out[] = '<li class="page-item"><a class="page-link">...</a></li>';
    for ($nCont = $neighbors; $nCont >= 1; $nCont--)
        if ($start >= $kmess * $nCont) {
            $tmpStart = $start - $kmess * $nCont;
            $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
        }
    $out[] = '<li class="page-item active"><a class="page-link">' . ($start / $kmess + 1) . '</a></li>';
    $tmpMaxPages = (int) (($total - 1) / $kmess) * $kmess;
    for ($nCont = 1; $nCont <= $neighbors; $nCont++)
        if ($start + $kmess * $nCont <= $tmpMaxPages) {
            $tmpStart = $start + $kmess * $nCont;
            $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
        }
    if ($start + $kmess * ($neighbors + 1) < $tmpMaxPages)
        $out[] = '<li class="page-item"><a class="page-link">...</a></li>';
    if ($start + $kmess * $neighbors < $tmpMaxPages)
        $out[] = sprintf($base_link, $tmpMaxPages / $kmess + 1, $tmpMaxPages / $kmess + 1);
    if ($start + $kmess < $total) {
        $display_page = ($start + $kmess) > $total ? $total : ($start / $kmess + 2);
        $out[] = sprintf($base_link, $display_page, '<i class="fas fa-angle-right"></i>');
    }
    $out[] = '</ul></nav>';
    return implode('', $out);
}
function myip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    }
    return $ip_address;
}
function timeAgo($time_ago)
{
    if (!is_numeric($time_ago)) {
        $time_ago = strtotime($time_ago);
    }
    $cur_time = time();
    $time_elapsed = $cur_time - $time_ago;
    $seconds = $time_elapsed;
    $minutes = round($time_elapsed / 60);
    $hours = round($time_elapsed / 3600);
    $days = round($time_elapsed / 86400);
    $weeks = round($time_elapsed / 604800);
    $months = round($time_elapsed / 2600640);
    $years = round($time_elapsed / 31207680);
    // Seconds
    if ($seconds <= 60) {
        return "$seconds giây trước";
    }
    //Minutes
    else if ($minutes <= 60) {
        return "$minutes phút trước";
    }
    //Hours
    else if ($hours <= 24) {
        return "$hours tiếng trước";
    }
    //Days
    else if ($days <= 7) {
        if ($days == 1) {
            return "Hôm qua";
        } else {
            return "$days ngày trước";
        }
    }
    //Weeks
    else if ($weeks <= 4.3) {
        return "$weeks tuần trước";
    }
    //Months
    else if ($months <= 12) {
        return "$months tháng trước";
    }
    //Years
    else {
        return "$years năm trước";
    }
}

function admin_phantrang($url, $start, $total, $per_page, $page_param = 'page')
{
    if ($total <= $per_page) {
        return ''; // No pagination needed
    }

    $total_pages = ceil($total / $per_page);
    $current_page = floor($start / $per_page) + 1;
    $neighbors = 2;

    $out = '<div class="flex justify-center items-center gap-2 mt-6">';

    // Previous button
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $out .= '<a href="' . $url . $page_param . '=' . $prev_page . '" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">';
        $out .= '<i class="fas fa-chevron-left"></i></a>';
    }

    // First page
    if ($current_page > $neighbors + 1) {
        $out .= '<a href="' . $url . $page_param . '=1" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">1</a>';
        if ($current_page > $neighbors + 2) {
            $out .= '<span class="px-3 py-2">...</span>';
        }
    }

    // Pages around current
    for ($i = max(1, $current_page - $neighbors); $i <= min($total_pages, $current_page + $neighbors); $i++) {
        if ($i == $current_page) {
            $out .= '<span class="px-3 py-2 bg-blue-600 text-white border border-blue-600 rounded-lg font-semibold">' . $i . '</span>';
        } else {
            $out .= '<a href="' . $url . $page_param . '=' . $i . '" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">' . $i . '</a>';
        }
    }

    // Last page
    if ($current_page < $total_pages - $neighbors) {
        if ($current_page < $total_pages - $neighbors - 1) {
            $out .= '<span class="px-3 py-2">...</span>';
        }
        $out .= '<a href="' . $url . $page_param . '=' . $total_pages . '" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">' . $total_pages . '</a>';
    }

    // Next button
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $out .= '<a href="' . $url . $page_param . '=' . $next_page . '" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">';
        $out .= '<i class="fas fa-chevron-right"></i></a>';
    }

    $out .= '</div>';

    // Add info text
    $showing_from = $start + 1;
    $showing_to = min($start + $per_page, $total);
    $out .= '<div class="text-center text-sm text-gray-600 mt-3">Hiển thị ' . $showing_from . ' - ' . $showing_to . ' trong tổng số ' . $total . ' mục</div>';

    return $out;
}

function send_telegram_notification($message)
{
    global $CMSNT;
    $bot_token = $CMSNT->site('telegram_bot_token');
    $chat_id = $CMSNT->site('telegram_chat_id');
    
    if (empty($bot_token) || empty($chat_id)) {
        return false;
    }
    
    $url = "https://api.telegram.org/bot" . $bot_token . "/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $resp = curl_exec($ch);
    curl_close($ch);
    
    return $resp;
}
/* CMSNT.CO TEAM LEADER - NTTHANH - DEV PHP */




