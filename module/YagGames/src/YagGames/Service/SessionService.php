<?php

namespace YagGames\Service;

class SessionService
{
    protected $sessionContainer;

    public function setSessionContainer(
        $sessionContainer
    ) {
        $this->sessionContainer = $sessionContainer;
    }

    public function __invoke() {
        return $this->sessionContainer;
    }
}