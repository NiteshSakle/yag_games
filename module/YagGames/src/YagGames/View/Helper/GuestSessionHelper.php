<?php

namespace YagGames\View\Helper;

use Zend\View\Helper\AbstractHelper;

class GuestSessionHelper extends AbstractHelper
{
    protected $guestSessionService;

    public function setSessionService(
        $guestSessionService
    ) {
        $this->guestSessionService = $guestSessionService();       
    }

    public function __invoke() {
        return $this->guestSessionService;
    }
}