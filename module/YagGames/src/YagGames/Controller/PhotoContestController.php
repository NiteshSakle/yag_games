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

class PhotoContestController extends AbstractActionController
{

  public function onDispatch(\Zend\Mvc\MvcEvent $e)
  {
    return parent::onDispatch($e);
  }

  public function contestAction()
  {
    $contestId = $this->params()->fromQuery('contest_id', 0);
    
    
    $contestMediaTable = $this->getServiceLocator()->get('photoContestService');
    $data = $contestMediaTable->getContestMedia($contestId);

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
