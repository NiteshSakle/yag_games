<?php

namespace YagGames\Controller;

use Exception;
use Zend\Mvc\MvcEvent;
use Zend\Paginator\Adapter\NullFill;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;

class PhotoContestController extends BaseController
{

  public function onDispatch(MvcEvent $e)
  {
    return parent::onDispatch($e);
  }

  public function viewAction()
  {
    $page = $this->params()->fromRoute('page') ? (int) $this->params()->fromRoute('page') : 1;
    $size = $this->params()->fromRoute('size') ? (int) $this->params()->fromRoute('size') : 20;
    $contestId = $this->params()->fromRoute('id', null);
    $this->session = $this->sessionPlugin();
    $userId = '';
    if (isset($this->session->mem_id)) {
      $userId = $this->session->mem_id;
    }
    
    $contestMediaTable = $this->getServiceLocator()->get('photoContestService');
    $data = $contestMediaTable->getContestMedia($contestId, $userId, null, $page, $size);
    
    $paginator = new Paginator(new NullFill($data['total']));
    $paginator->setCurrentPageNumber($page);
    $paginator->setItemCountPerPage($size);
    
    $vm = new ViewModel();
    $vm->setVariable('paginator', $paginator);
    $vm->setVariable('medias', $data['medias']);
    $vm->setVariable('contestId', $contestId);
    return $vm;
  }
  
  public function submissionAction()
  {
    $contestId = $this->params()->fromRoute('id', null);
    $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
    $contest = $contestTable->fetchRecord($contestId); 
    if (!$contest) {
      throw new Exception("No contest found", 404);
    }
    
    return new ViewModel(array('contest' => (array)$contest));
  }
  
  public function artSubmissionAction()
  {

  }
  
  public function rankingAction()
  {

  }
  
  public function voteAction()
  {

  }
  
  public function termsAction()
  {

  }
  
}
