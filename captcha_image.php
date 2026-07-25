<?php
session_start();
header("Content-type: image/png");

$code = '';
$chars = 'abcdefghijklmnopqrstuvwxyz23456789'; // Bỏ các ký tự dễ nhầm lẫn như 1, l, 0, o, O
for ($i = 0; $i < 5; $i++) {
    $code .= $chars[rand(0, strlen($chars) - 1)];
}
$_SESSION['image_captcha'] = strtolower($code);

$image = imagecreatetruecolor(120, 40);
$bg = imagecolorallocate($image, 30, 41, 59); // Slate-like background (#1E293B)
imagefill($image, 0, 0, $bg);

// Thêm các đường nhiễu
for ($i = 0; $i < 5; $i++) {
    $lineColor = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
    imageline($image, rand(0, 120), rand(0, 40), rand(0, 120), rand(0, 40), $lineColor);
}

// Thêm các ký tự vẽ bằng font mặc định của PHP
$textColor = imagecolorallocate($image, 255, 255, 255);
for ($i = 0; $i < strlen($code); $i++) {
    $char = $code[$i];
    $x = 15 + ($i * 18);
    $y = rand(10, 15);
    imagechar($image, 5, $x, $y, $char, $textColor);
}

imagepng($image);
imagedestroy($image);
?>
