<?php

namespace YagGames\Service;

class GuestSessionService
{
    protected $guestSessionContainer;

    public function setSessionContainer(
        $guestSessionContainer
    ) {
        $this->guestSessionContainer = $guestSessionContainer;
    }

    public function __invoke() {
        return $this->guestSessionContainer;
    }
}