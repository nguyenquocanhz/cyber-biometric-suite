<?php
/**
 * Spam Protection Helper
 * Chức năng chống spam nạp thẻ
 */

/**
 * Kiểm tra xem IP/User có bị block spam không
 * 
 * @param string $ip Địa chỉ IP
 * @param string $userId ID người dùng (id_game)
 * @param string $actionType Loại hành động (card_submit, login, register...)
 * @return array ['allowed' => bool, 'reason' => string|null, 'remaining' => int]
 */
function checkSpamProtection($ip, $userId = null, $actionType = 'card_submit')
{
    global $CMSNT;

    // Cấu hình mặc định
    $config = [
        'card_submit' => ['max_attempts' => 5, 'time_window' => 300, 'block_duration' => 3600],
        'card_submit_daily' => ['max_attempts' => 20, 'time_window' => 86400, 'block_duration' => 86400],
        'login' => ['max_attempts' => 10, 'time_window' => 300, 'block_duration' => 1800],
        'register' => ['max_attempts' => 3, 'time_window' => 3600, 'block_duration' => 86400],
    ];

    // Lấy cấu hình từ DB nếu có
    $dbConfig = $CMSNT->get_row("SELECT * FROM `rate_limit_config` WHERE `action_type` = '$actionType' AND `is_active` = 1");
    if ($dbConfig) {
        $maxAttempts = (int) $dbConfig['max_attempts'];
        $timeWindow = (int) $dbConfig['time_window'];
        $blockDuration = (int) $dbConfig['block_duration'];
    } else {
        $cfg = $config[$actionType] ?? $config['card_submit'];
        $maxAttempts = $cfg['max_attempts'];
        $timeWindow = $cfg['time_window'];
        $blockDuration = $cfg['block_duration'];
    }

    // 1. Kiểm tra IP blacklist
    $blacklisted = $CMSNT->get_row("
        SELECT * FROM `ip_blacklist` 
        WHERE `ip_address` = '$ip' 
        AND (`expires_at` IS NULL OR `expires_at` > NOW())
    ");

    if ($blacklisted) {
        return [
            'allowed' => false,
            'reason' => 'IP của bạn đã bị cấm: ' . ($blacklisted['reason'] ?? 'Vi phạm chính sách'),
            'remaining' => 0,
            'blocked_until' => $blacklisted['expires_at']
        ];
    }

    // 2. Kiểm tra đang bị block tạm thời
    $activeBlock = $CMSNT->get_row("
        SELECT * FROM `spam_log` 
        WHERE `ip_address` = '$ip' 
        AND `action_type` = '$actionType'
        AND `is_blocked` = 1 
        AND `expires_at` > NOW()
        ORDER BY `expires_at` DESC
        LIMIT 1
    ");

    if ($activeBlock) {
        return [
            'allowed' => false,
            'reason' => 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau.',
            'remaining' => 0,
            'blocked_until' => $activeBlock['expires_at']
        ];
    }

    // 3. Đếm số lần thử trong khoảng thời gian
    $attemptCount = $CMSNT->num_rows("
        SELECT 1 FROM `spam_log` 
        WHERE `ip_address` = '$ip' 
        AND `action_type` = '$actionType'
        AND `created_at` > DATE_SUB(NOW(), INTERVAL $timeWindow SECOND)
    ") ?: 0;

    $remaining = max(0, $maxAttempts - $attemptCount - 1);

    // 4. Kiểm tra vượt giới hạn
    if ($attemptCount >= $maxAttempts) {
        $blockedUntil = date('Y-m-d H:i:s', time() + $blockDuration);

        // Ghi log blocked
        $CMSNT->insert('spam_log', [
            'ip_address' => $ip,
            'user_id' => $userId,
            'action_type' => $actionType,
            'is_blocked' => 1,
            'block_reason' => "Vượt quá $maxAttempts lần trong " . ($timeWindow / 60) . " phút",
            'expires_at' => $blockedUntil,
            'attempt_count' => $attemptCount + 1,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return [
            'allowed' => false,
            'reason' => "Bạn đã gửi quá $maxAttempts yêu cầu. Vui lòng thử lại sau " . ($blockDuration / 60) . " phút.",
            'remaining' => 0,
            'blocked_until' => $blockedUntil
        ];
    }

    return [
        'allowed' => true,
        'reason' => null,
        'remaining' => $remaining,
        'blocked_until' => null
    ];
}

/**
 * Ghi log spam (gọi sau khi kiểm tra thành công)
 */
function logSpamAttempt($ip, $userId = null, $actionType = 'card_submit', $extraData = [])
{
    global $CMSNT;

    $data = [
        'ip_address' => $ip,
        'user_id' => $userId,
        'action_type' => $actionType,
        'is_blocked' => 0,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];

    if (!empty($extraData['telco']))
        $data['telco'] = $extraData['telco'];
    if (!empty($extraData['amount']))
        $data['amount'] = $extraData['amount'];
    if (!empty($extraData['serial']))
        $data['serial'] = md5($extraData['serial']); // Hash serial
    if (!empty($extraData))
        $data['extra_data'] = json_encode($extraData);

    return $CMSNT->insert('spam_log', $data);
}

/**
 * Kiểm tra serial thẻ đã được sử dụng chưa (chống dùng lại)
 */
function isSerialUsed($serial, $telco = null)
{
    global $CMSNT;

    $serialHash = md5($serial);
    $where = "`serial` = '$serialHash'";
    if ($telco) {
        $where .= " AND `telco` = '$telco'";
    }

    return $CMSNT->num_rows("SELECT 1 FROM `spam_log` WHERE $where") > 0;
}

/**
 * Thêm IP vào blacklist
 */
function addToBlacklist($ip, $reason = null, $blockedBy = 'system', $expiresAt = null)
{
    global $CMSNT;

    // Kiểm tra đã tồn tại chưa
    $existing = $CMSNT->get_row("SELECT * FROM `ip_blacklist` WHERE `ip_address` = '$ip'");

    if ($existing) {
        return $CMSNT->update('ip_blacklist', [
            'reason' => $reason,
            'blocked_by' => $blockedBy,
            'expires_at' => $expiresAt
        ], "`ip_address` = '$ip'");
    }

    return $CMSNT->insert('ip_blacklist', [
        'ip_address' => $ip,
        'reason' => $reason,
        'blocked_by' => $blockedBy,
        'expires_at' => $expiresAt,
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Xóa IP khỏi blacklist
 */
function removeFromBlacklist($ip)
{
    global $CMSNT;
    return $CMSNT->remove('ip_blacklist', "`ip_address` = '$ip'");
}

/**
 * Xóa log spam cũ (cleanup)
 */
function cleanupSpamLog($days = 30)
{
    global $CMSNT;
    return $CMSNT->query("DELETE FROM `spam_log` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL $days DAY)");
}
