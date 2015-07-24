<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace YagGames\Controller;

use Zend\View\Model\ViewModel;

class ContestController extends BaseController
{

  public function onDispatch(\Zend\Mvc\MvcEvent $e)
  {
    $viewModel = $e->getViewModel();
    $this->session = $this->sessionPlugin();
    return parent::onDispatch($e);
  }

  public function newContestAction()
  {
    $request = $this->getRequest();
    if ($request->isPost()) {
      $page = $this->getRequest()->getPost('page', 1);
    }
    return $this->getContestList('new', $page);
  }

  public function activeContestAction()
  {
    $request = $this->getRequest();
    if ($request->isPost()) {
      $page = $this->getRequest()->getPost('page', 1);
    }
    return $this->getContestList('active', $page);
  }

  public function pastContestAction()
  {
    $request = $this->getRequest();
    if ($request->isPost()) {
      $page = $this->getRequest()->getPost('page', 1);
    }
    return $this->getContestList('past', $page);
  }

  public function myContestAction()
  {
    $this->checkLogin();

    $request = $this->getRequest();
    if ($request->isPost()) {
      $page = $this->getRequest()->getPost('page', 1);
    }
    return $this->getContestList('my', $page);
  }

  private function getContestList($type, $page)
  {
    $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
    $data = $contestTable->fetchAllByType($type, $page);

    return $this->getViewModal(array('data' => $data, 'type' => $type, 'page' => $page));
  }

  private function getViewModal($data)
  {
    $view = new ViewModel($data);
    $view->setTemplate('yag-games/contest/index');

    return $view;
  }

}
