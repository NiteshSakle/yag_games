<?php

namespace YagGames\View\Helper;

use Zend\View\Helper\AbstractHelper;

class OrdinalHelper extends AbstractHelper
{

    private $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');

    public function __invoke($number, $suffix = FALSE)
    {
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {

            if ($suffix === TRUE) {
                return $number . ' <sup>th</sup>';
            }

            return $number . 'th';
        } else {

            if ($suffix === TRUE) {
                return $number . ' <sup>' . $this->ends[$number % 10] . '</sup>';
            }

            return $number . $this->ends[$number % 10];
        }
    }

}