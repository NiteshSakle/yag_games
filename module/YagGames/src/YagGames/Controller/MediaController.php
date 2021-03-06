<?php

namespace YagGames\Controller;

use Zend\View\Model\ViewModel;

class MediaController extends BaseController
{

  public function getMyMediaAction()
  {
    $this->checkLogin();
    
    $page = $this->params()->fromRoute('page') ? (int) $this->params()->fromRoute('page') : 1;
    $size = $this->params()->fromRoute('size') ? (int) $this->params()->fromRoute('size') : 10;
    $mediaId = $this->params()->fromRoute('mid') ? (int) $this->params()->fromRoute('mid') : 0;
    $contestTable = $this->getServiceLocator()->get('YagGames\Model\MediaViewTable');
    if ($mediaId > 0) {
        $rank = $contestTable->getMyMediaRank($this->session->mem_id, $mediaId);
        if ($rank) {
            $page = ceil($rank/$size);
        }
    }
    
    $data = $contestTable->getMyMedia($this->session->mem_id, $page, $size);
    
    $paginator = new \Zend\Paginator\Paginator(new \Zend\Paginator\Adapter\NullFill($data['total']));
    $paginator->setCurrentPageNumber($page);
    $paginator->setItemCountPerPage($size);
    
    $vm = new ViewModel();
    $vm->setVariable('paginator', $paginator);
    $vm->setVariable('medias', $data['medias']);
    $vm->setVariable('mediaId', $mediaId);
    $vm->setTerminal(true);
    return $vm;
  }
  
}
