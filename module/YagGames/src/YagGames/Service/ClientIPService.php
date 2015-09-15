<?php

namespace YagGames\Service;

class ClientIPService 
{
    private $serviceManager;

    public function __construct($serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    public function getClientIPAddress() 
    {   
        $request = $this->serviceManager->get('Request');        
        
        $ipaddress = '';
        if (!empty($request->getServer('HTTP_CLIENT_IP')))
            $ipaddress = $request->getServer('HTTP_CLIENT_IP');
        else if (!empty($request->getServer('HTTP_X_FORWARDED_FOR')))
            $ipaddress = $request->getServer('HTTP_X_FORWARDED_FOR');
        else if (!empty($request->getServer('HTTP_X_FORWARDED')))
            $ipaddress = $request->getServer('HTTP_X_FORWARDED');
        else if (!empty($request->getServer('HTTP_FORWARDED_FOR')))
            $ipaddress = $request->getServer('HTTP_FORWARDED_FOR');
        else if (!empty($request->getServer('HTTP_FORWARDED')))
            $ipaddress = $request->getServer('HTTP_FORWARDED');
        else if (!empty($request->getServer('REMOTE_ADDR')))
            $ipaddress = $request->getServer('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        
        return $ipaddress;
    }

}

