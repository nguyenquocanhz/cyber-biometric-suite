<?php
/**
 * Helper Functions for System
 * These functions are used to support the main application logic
 */

/**
 * Get a random active giftcode from database
 * @param object $CMSNT Database connection
 * @return string|false Returns giftcode or false if none available
 */
function getRandomGiftcode($CMSNT)
{
    $giftcode = $CMSNT->get_row("SELECT code FROM `giftcodes` WHERE `is_active` = 1 ORDER BY RAND() LIMIT 1");
    return $giftcode ? $giftcode['code'] : false;
}

/**
 * Check rate limit for an IP address
 * @param object $CMSNT Database connection
 * @param string $ip IP address to check
 * @param string $endpoint Endpoint being accessed
 * @param int $maxRequests Maximum requests allowed
 * @param int $timeWindow Time window in seconds
 * @return bool True if allowed, false if rate limited
 */
function checkRateLimit($CMSNT, $ip, $endpoint, $maxRequests = 10, $timeWindow = 60)
{
    $now = date('Y-m-d H:i:s');
    $timeAgo = date('Y-m-d H:i:s', time() - $timeWindow);

    // Check if IP is blocked
    $blocked = $CMSNT->get_row("SELECT * FROM `rate_limit` WHERE `ip_address` = '$ip' AND `endpoint` = '$endpoint' AND `blocked_until` > '$now'");
    if ($blocked) {
        return false;
    }

    // Get or create rate limit record
    $record = $CMSNT->get_row("SELECT * FROM `rate_limit` WHERE `ip_address` = '$ip' AND `endpoint` = '$endpoint'");

    if (!$record) {
        // Create new record
        $CMSNT->insert('rate_limit', [
            'ip_address' => $ip,
            'endpoint' => $endpoint,
            'request_count' => 1,
            'last_request' => $now
        ]);
        return true;
    }

    // Check if we're still in the time window
    if (strtotime($record['last_request']) < strtotime($timeAgo)) {
        // Reset counter
        $CMSNT->update('rate_limit', [
            'request_count' => 1,
            'last_request' => $now,
            'blocked_until' => null
        ], "`ip_address` = '$ip' AND `endpoint` = '$endpoint'");
        return true;
    }

    // Increment counter
    $newCount = $record['request_count'] + 1;

    if ($newCount > $maxRequests) {
        // Block for 5 minutes
        $blockUntil = date('Y-m-d H:i:s', time() + 300);
        $CMSNT->update('rate_limit', [
            'request_count' => $newCount,
            'last_request' => $now,
            'blocked_until' => $blockUntil
        ], "`ip_address` = '$ip' AND `endpoint` = '$endpoint'");
        return false;
    }

    // Update counter
    $CMSNT->update('rate_limit', [
        'request_count' => $newCount,
        'last_request' => $now
    ], "`ip_address` = '$ip' AND `endpoint` = '$endpoint'");

    return true;
}

/**
 * Sanitize and validate ID Game
 * @param string $idGame ID game to validate
 * @return string|false Returns sanitized ID or false if invalid
 */
function validateIdGame($idGame)
{
    // Remove non-numeric characters
    $idGame = preg_replace('/[^0-9]/', '', $idGame);

    // Check length (Free Fire IDs are typically 9-12 digits)
    if (strlen($idGame) < 6 || strlen($idGame) > 15) {
        return false;
    }

    return $idGame;
}

/**
 * Validate card data
 * @param array $data Card data to validate
 * @return array Returns ['valid' => bool, 'errors' => array]
 */
function validateCardData($data)
{
    $errors = [];

    // Validate telco
    $validTelcos = ['VIETTEL', 'MOBIFONE', 'VINAPHONE', 'VIETNAMOBILE'];
    if (empty($data['telco']) || !in_array(strtoupper($data['telco']), $validTelcos)) {
        $errors[] = 'Loại thẻ không hợp lệ';
    }

    // Validate amount
    $validAmounts = [10000, 20000, 50000, 100000, 200000, 500000, 1000000];
    if (empty($data['amount']) || !in_array((int) $data['amount'], $validAmounts)) {
        $errors[] = 'Mệnh giá không hợp lệ';
    }

    // Validate serial (typically 11-14 digits for Vietnamese cards)
    if (empty($data['serial']) || !preg_match('/^[0-9]{10,15}$/', $data['serial'])) {
        $errors[] = 'Số seri không hợp lệ';
    }

    // Validate PIN (typically 12-15 digits)
    if (empty($data['pin']) || !preg_match('/^[0-9]{10,16}$/', $data['pin'])) {
        $errors[] = 'Mã thẻ không hợp lệ';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}
