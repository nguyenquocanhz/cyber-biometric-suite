<?php
require_once(__DIR__ . "/GatewayInterface.php");

class GatewayFactory
{
    /**
     * Create gateway instance by name
     * @param string $name Gateway identifier (doithe1s, thesieure)
     * @return GatewayInterface
     */
    public static function create($name)
    {
        $name = strtolower(trim($name));
        
        switch ($name) {
            case 'thesieure':
                require_once(__DIR__ . "/TheSieuReGateway.php");
                return new TheSieuReGateway();
            
            case 'doithe1s':
            default:
                require_once(__DIR__ . "/DoiThe1sGateway.php");
                return new DoiThe1sGateway();
        }
    }
}
