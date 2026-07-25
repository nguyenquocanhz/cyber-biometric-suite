<?php
require_once(__DIR__ . "/GatewayInterface.php");

class DoiThe1sGateway implements GatewayInterface
{
    protected $apiUrl = 'https://doithe1s.vn/chargingws/v2';

    public function processCard($params)
    {
        global $CMSNT;
        $partner_id = $CMSNT->site('partner_id');
        $partner_key = $CMSNT->site('partner_key');

        if (empty($partner_id) || empty($partner_key)) {
            return [
                'success' => false,
                'message' => 'Cấu hình cổng gạch thẻ (Partner ID/Key) trống!'
            ];
        }

        $postData = [
            'telco' => strtoupper($params['telco']),
            'code' => $params['pin'],
            'serial' => $params['serial'],
            'amount' => (int)$params['amount'],
            'request_id' => $params['request_id'],
            'partner_id' => $partner_id,
            'sign' => md5($partner_key . $params['pin'] . $params['serial']),
            'command' => 'charging'
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'success' => false,
                'message' => 'Lỗi kết nối cổng: ' . $error
            ];
        }

        $result = json_decode($response, true);
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Phản hồi từ cổng không hợp lệ: ' . $response
            ];
        }

        // Phản hồi tiêu chuẩn từ cổng gạch thẻ: status 99 là đang chờ duyệt trên cổng, status 1 là thành công
        if (isset($result['status']) && ($result['status'] == 99 || $result['status'] == 1)) {
            return [
                'success' => true,
                'message' => $result['message'] ?? 'Gửi thẻ lên cổng thành công!'
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Lỗi cổng gạch thẻ (Mã lỗi: ' . ($result['status'] ?? 'unknown') . ')'
        ];
    }
}
