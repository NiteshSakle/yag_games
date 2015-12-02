<?php
namespace YagGames\Service;

class MediaImageService
{
  protected $kcryptService;
  private $config;

  public function setKCryptService($kcryptService, $config)
  {
    $this->kcryptService = $kcryptService;
    $this->config = $config;
  }

  public function __invoke($media, $type , $size = "null")
  {   
    $sizes = ($size === "null") ? "" : "&size=" . $size ;
    $version = ($this->config['main_site']['image_version'] === "null") ? "" : "&v=" . $this->config['main_site']['image_version'] ;
    $mediaUrl = $this->config['main_site']['url'];
    if ($this->config['main_site']['cloudfront_url']) {
      $mediaUrl = $this->config['main_site']['cloudfront_url'];
    }
    
    $imglink = $mediaUrl .'/'. 
               $type .'/'.
               $this->kcryptService->enc($media['media_id']) .'/'.
               $this->kcryptService->enc($media['folder_id']) .'/photo.jpg' . $sizes . $version;

    return $imglink;
  }
}

