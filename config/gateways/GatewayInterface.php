<?php
/**
 * Interface for Payment/Card Charging Gateways
 */
interface GatewayInterface
{
    /**
     * Process card charging request
     * @param array $params Contains: telco, pin, serial, amount, request_id
     * @return array Status containing 'success' (bool) and 'message' (string)
     */
    public function processCard($params);
}
