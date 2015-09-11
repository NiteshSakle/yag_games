<?php

namespace YagGames\Service;

use Zend\Http\Request;

class FbScrapService
{
    private $serviceManager;

    public function __construct($serviceManager)
    {
      $this->serviceManager = $serviceManager;
    }    

    public function informFbToScrap($contestId, $mediaId = null) 
    {
        $config = $this->getServiceLocator()->get('Config');
        try {
            if(!$mediaId) {
                $url = $config['main_site']['url'] . '/contests/details/id/' . $contestId;
            } else {
                $url = $config['main_site']['url'] . '/contests/details/id/' . $contestId . '/mid/' .$mediaId;            
            }
            $url = preg_replace("/ /", "%20", $url);
            
            if($config['main_site']['cloudfront_url'] != '') {
                file_get_contents("https://graph.facebook.com/?id=" . urlencode($url) . '&scrape=true');
            }
        } catch (Exception $ex) {
        }        
    }    

    private function getServiceLocator()
    {
      return $this->serviceManager;
    }
}
