<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
// Load .env file manually if exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Handle quotes and inline comments robustly
            if (preg_match('/^"((?:[^"\\\\]|\\\\.)*)"/', $value, $matches)) {
                $value = stripcslashes($matches[1]);
            } elseif (preg_match("/^'((?:[^'\\\\]|\\\\.)*)'/", $value, $matches)) {
                $value = stripcslashes($matches[1]);
            } else {
                if (($pos = strpos($value, '#')) !== false) {
                    $value = substr($value, 0, $pos);
                }
                $value = trim($value);
            }
            
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

global $base_url;
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https://' : 'http://';
    $base_url = $protocol . $_SERVER['HTTP_HOST'] . '/';
} else {
    $base_url = $_ENV['APP_URL'] ?? 'https://shopkcvip.cc/';
}
if (substr($base_url, -1) !== '/') {
    $base_url .= '/';
}

class CMSNT
{
    private $ketnoi;
    function connect()
    {
        if (!$this->ketnoi) {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $user = $_ENV['DB_USER'] ?? 'shopkcff1_acc';
            $pass = $_ENV['DB_PASS'] ?? 'shopkcff1_acc';
            $db   = $_ENV['DB_NAME'] ?? 'shopkcff1_acc';
            $this->ketnoi = @mysqli_connect($host, $user, $pass, $db);
            if (!$this->ketnoi) {
                $error_msg = mysqli_connect_error();
                die("
                <div style='padding: 30px; background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; border-radius: 12px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; max-width: 650px; margin: 80px auto; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
                    <h3 style='margin-top: 0; color: #e53e3e; font-size: 1.4em; display: flex; align-items: center; gap: 8px;'>
                        <span style='font-size: 1.2em;'>⚠️</span> Lỗi Kết Nối Cơ Sở Dữ Liệu
                    </h3>
                    <p style='color: #4a5568; line-height: 1.6;'>Hệ thống không thể kết nối đến MySQL. Vui lòng kiểm tra lại cấu hình trong tệp <strong>.env</strong> tại thư mục gốc.</p>
                    
                    <div style='background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin: 20px 0;'>
                        <strong style='color: #2d3748;'>Chi tiết lỗi:</strong> 
                        <code style='display: block; background: #f7fafc; padding: 8px; border-radius: 4px; margin-top: 8px; color: #e53e3e; font-family: monospace; font-size: 0.95em;'>$error_msg</code>
                    </div>

                    <strong style='color: #2d3748;'>Thông tin kết nối hiện tại (Đọc từ .env):</strong>
                    <ul style='margin-top: 8px; color: #4a5568; line-height: 1.6;'>
                        <li>Host: <code>$host</code></li>
                        <li>User: <code>$user</code></li>
                        <li>Database: <code>$db</code></li>
                    </ul>
                    
                    <hr style='border: 0; border-top: 1px solid #fed7d7; margin: 25px 0;'>
                    <p style='font-size: 0.9em; color: #718096; margin-bottom: 0;'>
                        💡 <strong>Mẹo cPanel:</strong> Chắc chắn rằng bạn đã <strong>tạo User</strong>, <strong>tạo Database</strong> và thực hiện bước quan trọng là <strong>Add User To Database (Gán quyền)</strong> đầy đủ trong giao diện Quản lý MySQL của cPanel.
                    </p>
                </div>
                ");
            }
            mysqli_query($this->ketnoi, "set names 'utf8'");
        }
    }
    function dis_connect()
    {
        if ($this->ketnoi) {
            mysqli_close($this->ketnoi);
        }
    }
    function escape($str)
    {
        $this->connect();
        return mysqli_real_escape_string($this->ketnoi, $str);
    }
    function getUser($username)
    {
        $this->connect();
        $username = mysqli_real_escape_string($this->ketnoi, $username);
        $result = $this->ketnoi->query("SELECT * FROM `users` WHERE `username` = '$username' ");
        return $result ? $result->fetch_array() : null;
    }
    function site($data)
    {
        $this->connect();
        $data = mysqli_real_escape_string($this->ketnoi, $data);
        $result = $this->ketnoi->query("SELECT * FROM `options` WHERE `name` = '$data' ");
        $row = $result ? $result->fetch_array() : null;
        return $row['value'] ?? '';
    }
    function query($sql)
    {
        $this->connect();
        $row = $this->ketnoi->query($sql);
        return $row;
    }
    function cong($table, $data, $sotien, $where)
    {
        $this->connect();
        $row = $this->ketnoi->query("UPDATE `$table` SET `$data` = `$data` + '$sotien' WHERE $where ");
        return $row;
    }
    function tru($table, $data, $sotien, $where)
    {
        $this->connect();
        $row = $this->ketnoi->query("UPDATE `$table` SET `$data` = `$data` - '$sotien' WHERE $where ");
        return $row;
    }
    function insert($table, $data)
    {
        $this->connect();
        $field_list = '';
        $value_list = '';
        foreach ($data as $key => $value) {
            $field_list .= ",$key";
            $value_list .= ",'" . mysqli_real_escape_string($this->ketnoi, $value) . "'";
        }
        $sql = 'INSERT INTO ' . $table . '(' . trim($field_list, ',') . ') VALUES (' . trim($value_list, ',') . ')';

        return mysqli_query($this->ketnoi, $sql);
    }
    function update($table, $data, $where)
    {
        $this->connect();
        $sql = '';
        foreach ($data as $key => $value) {
            $sql .= "$key = '" . mysqli_real_escape_string($this->ketnoi, $value) . "',";
        }
        $sql = 'UPDATE ' . $table . ' SET ' . trim($sql, ',') . ' WHERE ' . $where;
        return mysqli_query($this->ketnoi, $sql);
    }
    function update_value($table, $data, $where, $value1)
    {
        $this->connect();
        $sql = '';
        foreach ($data as $key => $value) {
            $sql .= "$key = '" . mysqli_real_escape_string($this->ketnoi, $value) . "',";
        }
        $sql = 'UPDATE ' . $table . ' SET ' . trim($sql, ',') . ' WHERE ' . $where . ' LIMIT ' . $value1;
        return mysqli_query($this->ketnoi, $sql);
    }
    function remove($table, $where)
    {
        $this->connect();
        $sql = "DELETE FROM $table WHERE $where";
        return mysqli_query($this->ketnoi, $sql);
    }
    function get_list($sql)
    {
        $this->connect();
        $result = mysqli_query($this->ketnoi, $sql);
        if (!$result) {
            die('Câu truy vấn bị sai');
        }
        $return = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $return[] = $row;
        }
        mysqli_free_result($result);
        return $return;
    }
    function get_row($sql)
    {
        $this->connect();
        $result = mysqli_query($this->ketnoi, $sql);
        if (!$result) {
            die('Câu truy vấn bị sai');
        }
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        if ($row) {
            return $row;
        }
        return false;
    }
    function num_rows($sql)
    {
        $this->connect();
        $result = mysqli_query($this->ketnoi, $sql);
        if (!$result) {
            die('Câu truy vấn bị sai');
        }
        $row = mysqli_num_rows($result);
        mysqli_free_result($result);
        if ($row) {
            return $row;
        }
        return false;
    }
}

function CheckLogin()
{
    global $my_username;
    if ($my_username != True) {
        return die('<script type="text/javascript">setTimeout(function(){ location.href = "' . BASE_URL('') . '" }, 0);</script>');
    }
}
function CheckAdmin()
{
    global $my_level;
    if ($my_level != 'admin') {
        return die('<script type="text/javascript">setTimeout(function(){ location.href = "' . BASE_URL('') . '" }, 0);</script>');
    }
}

class CmsntExitException extends Exception {}

function cmsnt_header($string) {
    if (!headers_sent()) {
        header($string);
    }
}

function cmsnt_response_code($code) {
    if (!headers_sent()) {
        http_response_code($code);
    }
}

function cmsnt_exit() {
    if (defined('TESTING') && TESTING === true) {
        throw new CmsntExitException("Exit called");
    }
    exit;
}
?>