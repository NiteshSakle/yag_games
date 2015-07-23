<?php

namespace YagGames\View\Helper;

use Zend\View\Helper\AbstractHelper;

class KCryptHelper extends AbstractHelper
{

  protected $kcryptService;

  public function setKCryptService($kcryptService)
  {
    $this->kcryptService = $kcryptService;
  }

  public function enc($string)
  {

    return $this->kcryptService->enc($string);
  }

  public function dec($string)
  {

    return $this->kcryptService->dec($string);
  }

}
