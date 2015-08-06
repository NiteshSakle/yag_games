<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace YagGames\Controller;

use Zend\Mvc\MvcEvent;
use Zend\Paginator\Adapter\NullFill;
use Zend\Paginator\Paginator;
use Zend\View\Model\ViewModel;

class ContestController extends BaseController
{

  public function onDispatch(MvcEvent $e)
  {
    $this->session = $this->sessionPlugin();
    
    $this->page = $this->params()->fromRoute('page') ? (int) $this->params()->fromRoute('page') : 1;
    $this->size = $this->params()->fromRoute('size') ? (int) $this->params()->fromRoute('size') : 10;
    $this->userId = null;
    if (isset($this->session->mem_id)) {
      $this->userId = $this->session->mem_id;
    }
    
    return parent::onDispatch($e);
  }

  public function newContestAction()
  {
    return $this->getContestList('new');
  }

  public function activeContestAction()
  {
    return $this->getContestList('active');
  }

  public function pastContestAction()
  {
    return $this->getContestList('past');
  }

  public function myContestAction()
  {
    $this->checkLogin();

    return $this->getContestList('my');
  }
  
   public function exclusiveContestAction()
  {
    $this->checkLogin();

    return $this->getContestList('exclusive');
  }

  private function getContestList($type)
  {
    $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
    $data = $contestTable->fetchAllByType($type, $this->userId, $this->page, $this->size);
    
    $paginator = new Paginator(new NullFill($data['total']));
    $paginator->setCurrentPageNumber($this->page);
    $paginator->setItemCountPerPage($this->size);
    foreach ($data['contests'] as $key => $contest) {
        $data['contests'][$key]['entry_end_date'] = date("jS F, Y", strtotime($contest['entry_end_date'])); 
        $data['contests'][$key]['winners_announce_date'] = date("jS F, Y", strtotime($contest['winners_announce_date'])); 
    }
    return $this->getViewModal(array(
        'paginator' => $paginator,
        'data' => $data['contests'], 
        'type' => $type, 
        'page' => $this->page,
        'size' => $this->size
    ));
  }

  private function getViewModal($data)
  {
    $view = new ViewModel($data);
    $view->setTemplate('yag-games/contest/index');

    return $view;
  }

}
