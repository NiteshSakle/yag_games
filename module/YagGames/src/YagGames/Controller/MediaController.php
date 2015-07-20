<?php

namespace YagGames\Controller;

use Zend\View\Model\JsonModel;

class MediaController extends BaseController
{

  public function getMyMediaAction()
  {
    $this->checkLogin();
    
    $page = $this->params()->fromQuery('page', 1);
    $contestTable = $this->getServiceLocator()->get('YagGames\Model\MediaViewTable');
    $data = $contestTable->getMyMedia($this->session->mem_id, $page);

    return new JsonModel($data);
  }
  
}
