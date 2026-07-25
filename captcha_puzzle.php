<?php
session_start();

// Xử lý xác thực tức thời (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
    $saved_x = isset($_SESSION['puzzle_captcha_x']) ? (int)$_SESSION['puzzle_captcha_x'] : -999;
    
    // Dung sai cho phép lệch 6px
    if (abs($offset - $saved_x) <= 6) {
        $_SESSION['puzzle_verified'] = true;
        echo json_encode(['status' => 'success', 'msg' => 'Xác thực thành công']);
    } else {
        $_SESSION['puzzle_verified'] = false;
        echo json_encode(['status' => 'error', 'msg' => 'Vị trí chưa khớp']);
    }
    exit;
}

// Xử lý sinh ảnh ghép mới (GET)
header('Content-Type: application/json');

$width = 260;
$height = 150;
$puzzleSize = 40;

// 1. Tải ngẫu nhiên một hình ảnh nền Free Fire thực tế từ thư viện
$bgImages = [
    __DIR__ . '/images/captcha/bg1.png',
    __DIR__ . '/images/captcha/bg2.png'
];

$randomBgPath = $bgImages[array_rand($bgImages)];

if (!file_exists($randomBgPath)) {
    // Fallback sang hình ảnh gradient nếu không tìm thấy tệp (để chống sập hệ thống)
    $bgImg = imagecreatetruecolor($width, $height);
    for ($y = 0; $y < $height; $y++) {
        $r = 30 + (int)(($y / $height) * 40);
        $g = 41 + (int)(($y / $height) * 20);
        $b = 59 + (int)(($y / $height) * 40);
        $color = imagecolorallocate($bgImg, $r, $g, $b);
        imageline($bgImg, 0, $y, $width, $y, $color);
    }
} else {
    // Tải ảnh thực tế và resize về đúng kích thước chuẩn 260x150
    $originalImg = imagecreatefrompng($randomBgPath);
    $bgImg = imagecreatetruecolor($width, $height);
    imagecopyresampled($bgImg, $originalImg, 0, 0, 0, 0, $width, $height, imagesx($originalImg), imagesy($originalImg));
    imagedestroy($originalImg);
}

// 2. Định nghĩa vị trí ngẫu nhiên của mảnh ghép mục tiêu
$targetX = rand(60, $width - $puzzleSize - 20);
$targetY = rand(15, $height - $puzzleSize - 15);
$_SESSION['puzzle_captcha_x'] = $targetX;
$_SESSION['puzzle_verified'] = false; // Reset trạng thái xác thực

// 3. Tạo ảnh mảnh ghép có độ trong suốt nền
$pieceImg = imagecreatetruecolor($puzzleSize, $puzzleSize);
imagealphablending($pieceImg, false);
imagesavealpha($pieceImg, true);
$transparent = imagecolorallocatealpha($pieceImg, 0, 0, 0, 127);
imagefill($pieceImg, 0, 0, $transparent);

// Cắt từ nền sang mảnh ghép trước khi vẽ bóng khuyết
imagecopy($pieceImg, $bgImg, 0, 0, $targetX, $targetY, $puzzleSize, $puzzleSize);

// Viền trắng nổi bật cho mảnh ghép
$whiteBorder = imagecolorallocate($pieceImg, 255, 255, 255);
imagerectangle($pieceImg, 0, 0, $puzzleSize - 1, $puzzleSize - 1, $whiteBorder);

// 4. Vẽ bóng mờ (ô khuyết) lên ảnh nền
$shadowColor = imagecolorallocatealpha($bgImg, 0, 0, 0, 85);
imagefilledrectangle($bgImg, $targetX, $targetY, $targetX + $puzzleSize - 1, $targetY + $puzzleSize - 1, $shadowColor);
imagerectangle($bgImg, $targetX, $targetY, $targetX + $puzzleSize - 1, $targetY + $puzzleSize - 1, $whiteBorder);

// 5. Xuất định dạng Base64
ob_start();
imagepng($bgImg);
$bgBase64 = base64_encode(ob_get_clean());
imagedestroy($bgImg);

ob_start();
imagepng($pieceImg);
$pieceBase64 = base64_encode(ob_get_clean());
imagedestroy($pieceImg);

// Dọn dẹp output buffer
if (ob_get_length()) ob_clean();

echo json_encode([
    'bg' => 'data:image/png;base64,' . $bgBase64,
    'piece' => 'data:image/png;base64,' . $pieceBase64,
    'y' => $targetY
]);
?>
