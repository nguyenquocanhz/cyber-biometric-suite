-- Drop old tables if exist to refresh schema
DROP TABLE IF EXISTS `users`, `options`, `napthe`, `telcos`, `logs`, `spam_log`, `ip_blacklist`, `card_fees`, `categories`, `comments`, `giftcodes`, `orders`, `packages`, `sliders`;

-- Complete Database schema for ShopKCFF

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NULL,
  `money` DECIMAL(15,2) DEFAULT 0.00,
  `level` VARCHAR(50) DEFAULT 'user',
  `banned` TINYINT(1) DEFAULT 0,
  `ip` VARCHAR(50) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `options` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `value` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `napthe` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_id` VARCHAR(100) NOT NULL UNIQUE,
  `id_game` VARCHAR(100) NOT NULL,
  `telco` VARCHAR(50) NOT NULL,
  `amount` INT NOT NULL,
  `thucnhan` INT DEFAULT 0,
  `serial` VARCHAR(100) NOT NULL,
  `code` VARCHAR(100) NOT NULL, -- Card PIN
  `status` INT DEFAULT 0, -- 0: pending, 1: success, 2: rejected, 99: auto success
  `ip` VARCHAR(50) NULL,
  `fingerprint` VARCHAR(255) NULL,
  `thoigian` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `thoigian_dt` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `telcos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `code` VARCHAR(50) NULL UNIQUE,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT 0,
  `content` TEXT NULL,
  `createdate` DATETIME NULL,
  `ip` VARCHAR(50) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `spam_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(50) NOT NULL,
  `user_id` INT DEFAULT 0,
  `action_type` VARCHAR(50) NULL,
  `telco` VARCHAR(50) NULL,
  `amount` INT NULL,
  `is_blocked` TINYINT(1) DEFAULT 0,
  `block_reason` VARCHAR(255) NULL,
  `attempt_count` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ip_blacklist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(50) NOT NULL UNIQUE,
  `reason` VARCHAR(255) NULL,
  `blocked_by` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `card_fees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `telco` VARCHAR(50) NOT NULL,
  `value` INT DEFAULT 0,
  `fees` INT DEFAULT 0,
  `penalty` INT DEFAULT 0,
  `status` TINYINT(1) DEFAULT 1,
  UNIQUE KEY `telco_value` (`telco`, `value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `content` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `giftcodes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(100) NOT NULL UNIQUE,
  `amount` INT DEFAULT 0,
  `status` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `game_id` INT NOT NULL,
  `amount` INT NOT NULL,
  `status` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `packages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `amount` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sliders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `image_url` VARCHAR(255) NULL,
  `order_priority` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default telcos
INSERT INTO `telcos` (`name`, `code`, `status`) VALUES 
('VIETTEL', 'VIETTEL', 1),
('MOBIFONE', 'MOBIFONE', 1),
('VINAPHONE', 'VINAPHONE', 1),
('VIETNAMOBILE', 'VIETNAMOBILE', 1),
('Garena', 'GARENA', 1)
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

-- Seed default admin user (devtech / anhanh123@@)
INSERT INTO `users` (`username`, `password`, `email`, `level`, `banned`) VALUES 
('devtech', '9d81d2c29b8934d17fdf8f1bca9dba51', 'admin@shopkcff.com', 'admin', 0)
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `level` = VALUES(`level`);

-- Seed default admin settings options
INSERT INTO `options` (`name`, `value`) VALUES 
('tenweb', 'SHOPKC FF - Trung Tâm Nạp Kim Cương Free Fire X5'),
('mota', 'Nạp Kim Cương Free Fire X5 tại SHOPKCFF – An toàn tuyệt đối, nhận kim cương chỉ sau 15 giây. Tự động xử lý 24/7.'),
('tukhoa', 'shopkcff, shopkcff.com, nap the free fire, nap kc ff, Garena Free Fire'),
('logo', '/images/mshop_header.webp'),
('favicon', '/images/ff-logo-icon.webp'),
('thongbao', '💎 Chào mừng bạn đến với Cổng Nạp Thẻ Free Fire X5 tự động! Nhận ngay Kim Cương ưu đãi tăng 20% giá trị thẻ nạp khi thanh toán.'),
('enable_turnstile_captcha', '1'),
('turnstile_site_key', '0x4AAAAAABHTj-O0ZB-5Ejdh'),
('turnstile_secret_key', '0x4AAAAAABHTj79I2zthQzkucAziT2ZZqpo'),
('enable_recaptcha_v2', '0'),
('recaptcha_v2_site_key', ''),
('recaptcha_v2_secret_key', ''),
('card_approval_mode', 'manual'), -- manual (nạp chậm), auto (nạp tự động)
('active_card_gateway', 'doithe1s'),
('partner_id', ''),
('partner_key', ''),
('enable_image_captcha', '0'), -- Bật/tắt captcha ảnh (0: tắt, 1: bật)
('enable_math_captcha', '0'),  -- Bật/tắt captcha phép tính (0: tắt, 1: bật)
('enable_puzzle_captcha', '0'), -- Bật/tắt captcha mảnh ghép trượt (0: tắt, 1: bật)
('enable_proxy_block', '0'), -- Bật/tắt chặn Proxy/VPN (0: tắt, 1: bật)
('enable_telegram_login', '0'), -- Bật/tắt đăng nhập bằng Telegram
('telegram_bot_username', ''), -- Tên Bot Telegram
('telegram_bot_token', ''), -- Token Bot Telegram
('telegram_chat_id', ''), -- Chat ID nhận thông báo Telegram
('site_api_key', 'shopkcff_secret_api_key_123') -- API Key để kết nối REST API
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
