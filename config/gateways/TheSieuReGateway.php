<?php
require_once(__DIR__ . "/DoiThe1sGateway.php");

class TheSieuReGateway extends DoiThe1sGateway
{
    // TheSieuRe.com dùng chung hoàn toàn định dạng API với DoiThe1s.vn, chỉ khác tên miền gọi API
    protected $apiUrl = 'https://thesieure.com/chargingws/v2';
}
