<?php

namespace YagGames\View\Helper;

use Zend\View\Helper\AbstractHelper;

class SessionHelper extends AbstractHelper
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