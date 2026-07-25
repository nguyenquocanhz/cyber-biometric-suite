<?php
namespace Tests;

use PHPUnit\Framework\TestCase;

// Load config first to define CMSNT class and exception wrapper
require_once __DIR__ . '/../config/config.php';

class NapTheApiTest extends TestCase
{
    private $originalPost;
    private $originalRequest;
    private $originalServer;

    protected function setUp(): void
    {
        $this->originalPost = $_POST;
        $this->originalRequest = $_REQUEST;
        $this->originalServer = $_SERVER;

        if (!defined('TESTING')) {
            define('TESTING', true);
        }

        global $base_url;
        $base_url = $_ENV['APP_URL'] ?? 'https://shopkcvip.cc/';
        if (substr($base_url, -1) !== '/') {
            $base_url .= '/';
        }

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        $_POST = $this->originalPost;
        $_REQUEST = $this->originalRequest;
        $_SERVER = $this->originalServer;
        global $CMSNT;
        $CMSNT = null;
    }

    public function testSubmitSlowCardRechargeSuccess()
    {
        global $CMSNT;

        // 1. Create a mock for CMSNT class
        $CMSNT = $this->createMock(\CMSNT::class);

        // Stub the site method to configure the system to slow/manual recharge
        $CMSNT->method('site')
            ->will($this->returnValueMap([
                ['site_api_key', 'test_secret_api_key'],
                ['card_approval_mode', 'manual'], // manual = slow card top-up, wait for approval
                ['telegram_bot_token', ''], // empty token skips real network calls
                ['telegram_chat_id', '']
            ]));

        // We expect exactly 1 call to insert transaction with status = 0 (pending)
        $CMSNT->expects($this->once())
            ->method('insert')
            ->with(
                $this->equalTo('napthe'),
                $this->callback(function ($data) {
                    return $data['id_game'] === '123456789' &&
                           $data['telco'] === 'viettel' &&
                           $data['amount'] === 100000 &&
                           $data['serial'] === '1234567890123' &&
                           $data['code'] === '9876543210987' &&
                           $data['status'] === 0 && // Waiting for approval (nạp thẻ chậm)
                           $data['fingerprint'] === 'REST_API';
                })
            )
            ->willReturn(true);

        // Mock parameters
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_REQUEST['api_key'] = 'test_secret_api_key';
        $_REQUEST['action'] = 'submit';

        $_POST['id_game'] = '123456789';
        $_POST['telco'] = 'viettel';
        $_POST['amount'] = 100000;
        $_POST['serial'] = '1234567890123';
        $_POST['pin'] = '9876543210987';

        // Capture API output
        ob_start();
        try {
            require __DIR__ . '/../api/napthe.php';
        } catch (\CmsntExitException $e) {
            // Expected script termination via cmsnt_exit
        }
        $output = ob_get_clean();

        // Assert response JSON
        $response = json_decode($output, true);
        $this->assertNotNull($response, "Output is not valid JSON: " . $output);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('manual', $response['approval_mode']);
        $this->assertEquals(0, $response['card_status']);
        $this->assertStringContainsString('Hệ thống đang chờ kiểm tra', $response['msg']);
    }
}
