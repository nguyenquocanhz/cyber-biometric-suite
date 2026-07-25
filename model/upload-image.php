<?php
/**
 * Image Upload API
 * Handles image uploads for games, banners, avatars
 * 
 * Request: FormData with 'image' file and 'type' parameter
 * Response: JSON with status and URL
 */

require_once(__DIR__ . "/../config/config.php");
require_once(__DIR__ . "/../config/function.php");

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    // Check if file was uploaded
    if (!isset($_FILES['image'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'msg' => 'No file uploaded'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $file = $_FILES['image'];
    $type = check_string($_POST['type'] ?? 'game'); // game, banner, avatar

    // Validate upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error: ' . $file['error']);
    }

    // Get file info
    $file_name = $file['name'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Validate file type
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($file_ext, $allowed_extensions)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'msg' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_extensions)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validate file size (2MB max)
    $max_size = 2 * 1024 * 1024; // 2MB
    if ($file_size > $max_size) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'msg' => 'File too large. Max size: 2MB'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_tmp);
    finfo_close($finfo);

    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime_type, $allowed_mimes)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'msg' => 'Invalid file type (MIME check failed)'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verify it's actually an image
    $img_info = getimagesize($file_tmp);
    if ($img_info === false) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'msg' => 'File is not a valid image'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Determine upload directory based on type
    $type_dirs = [
        'game' => 'games',
        'banner' => 'banners',
        'avatar' => 'avatars'
    ];

    $sub_dir = $type_dirs[$type] ?? 'games';
    $upload_dir = __DIR__ . '/../uploads/' . $sub_dir . '/';

    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $new_filename = uniqid() . '-' . time() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_filename;

    // Move uploaded file
    if (!move_uploaded_file($file_tmp, $upload_path)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Optional: Resize image for optimization
    // resizeImage($upload_path, 800, 600);

    // Generate public URL
    $public_url = '/uploads/' . $sub_dir . '/' . $new_filename;

    // Return success
    echo json_encode([
        'status' => 'success',
        'msg' => 'File uploaded successfully',
        'data' => [
            'url' => $public_url,
            'filename' => $new_filename,
            'size' => $file_size,
            'dimensions' => [
                'width' => $img_info[0],
                'height' => $img_info[1]
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Upload Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Upload failed: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Helper function to resize image (optional)
 */
function resizeImage($file, $max_width, $max_height)
{
    list($orig_width, $orig_height, $type) = getimagesize($file);

    // Calculate new dimensions
    $ratio = min($max_width / $orig_width, $max_height / $orig_height);
    if ($ratio >= 1)
        return; // No need to resize

    $new_width = (int) ($orig_width * $ratio);
    $new_height = (int) ($orig_height * $ratio);

    // Create image resource based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($file);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($file);
            break;
        case IMAGETYPE_WEBP:
            $src = imagecreatefromwebp($file);
            break;
        case IMAGETYPE_GIF:
            $src = imagecreatefromgif($file);
            break;
        default:
            return;
    }

    // Create new image
    $dst = imagecreatetruecolor($new_width, $new_height);

    // Preserve transparency for PNG/GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }

    // Resize
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);

    // Save based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($dst, $file, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($dst, $file, 9);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($dst, $file, 90);
            break;
        case IMAGETYPE_GIF:
            imagegif($dst, $file);
            break;
    }

    imagedestroy($src);
    imagedestroy($dst);
}
?>