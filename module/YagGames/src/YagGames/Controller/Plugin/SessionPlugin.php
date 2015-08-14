<?php

namespace YagGames\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class SessionPlugin extends AbstractPlugin
{
    protected $sessionService;

    public function setSessionService(
        $sessionService
    ) {
        $this->sessionService = $sessionService();
    }

    public function __invoke() {
        return $this->sessionService;
    }
}