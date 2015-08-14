<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace YagGames\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class FanFavoriteController extends AbstractActionController
{

  public function onDispatch(\Zend\Mvc\MvcEvent $e)
  {
    $viewModel = $e->getViewModel();
    $this->session = $this->sessionPlugin();
    return parent::onDispatch($e);
  }

  public function contestAction($id)
  {
    //     - search
    // - pagination
    // - sort by highest 
    // - should consider loged in user
    $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
    $data = $contestTable->fetchAllByType($type, $page);

    return array('data' => $data, 'type' => $type, 'page' => $page);
  }
  
  public function submissionAction()
  {
    
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
