<?php

namespace YagGames\View\Helper;

use Zend\View\Helper\AbstractHelper;

class MediaImageHelper extends AbstractHelper
{

  protected $kcryptService;

  public function setKCryptService($kcryptService)
  {
    $this->kcryptService = $kcryptService;
  }

  public function __invoke($media, $type , $size = "null")
  {
    $gameConfig = $this->getView()->plugin('config');
    $sizes = ($size === "null") ? "" : "&size=" . $size ;
    $mediaUrl = $gameConfig('main_site', 'url');
    if ($gameConfig('main_site', 'cloudfront_url')) {
      $mediaUrl = $gameConfig('main_site', 'cloudfront_url');
    }
    
    $imglink = $mediaUrl .'/'. 
               $type .'/'.
               $this->kcryptService->enc($media['media_id']) .'/'.
               $this->kcryptService->enc($media['folder_id']) . $sizes .'/photo.jpg';

    return $imglink;
  }

}
