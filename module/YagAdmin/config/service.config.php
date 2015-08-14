<?php

use YagGames\Service\SessionService;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container;

return array(
    
    'factories' => array(
                
        'YagAdmin\Logger' => function () {
            $log = new Logger();
            $writer = new Stream(dirname(__FILE__) . '/../../../data/log/adminlog');
            $log->addWriter($writer);
            return $log;
        },
                
        'adminSessionService' => function(ServiceLocatorInterface $serviceLocator) {
            $sessionContainer = new Container('admin_user');
            $sessionService = new SessionService();
            $sessionService->setSessionContainer($sessionContainer);
            return $sessionService;
        },
        
    ),
);
