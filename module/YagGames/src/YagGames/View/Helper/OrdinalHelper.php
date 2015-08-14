<?php

namespace YagGames\View\Helper;

use Zend\View\Helper\AbstractHelper;

class OrdinalHelper extends AbstractHelper
{

  private $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');

  public function __invoke($number)
  {
    if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
      return $number . 'th';
    } else {
      return $number . $this->ends[$number % 10];
    }
  }

}
