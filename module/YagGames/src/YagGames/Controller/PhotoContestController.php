<?php

namespace YagGames\Controller;

use Exception;
use YagGames\Exception\PhotoContestException;
use Zend\Mvc\MvcEvent;
use Zend\Paginator\Adapter\NullFill;
use Zend\Paginator\Paginator;
use Zend\View\Model\JsonModel;
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

    $photoContestService = $this->getServiceLocator()->get('photoContestService');
    $data = $photoContestService->getContestMedia($contestId, $userId, null, $page, $size);

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
    $this->checkLogin();

    $contestId = $this->params()->fromRoute('id', null);
    $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
    $contest = $contestTable->fetchRecord($contestId);
    if (!$contest) {
      throw new Exception("No contest found", 404);
    }
    
    $showPopupDiv = 0;
    if (isset($this->session['contestUpload']['contestMediaId'])) {
      unset($this->session['contestUpload']['contestMediaId']);
      $showPopupDiv = 1;      
    }

    return new ViewModel(array('contest' => (array) $contest, 'showPopupDiv' => $showPopupDiv));
  }
  
  public function uploadSubmissionAction()
  {
    $this->checkLogin();
    if (isset($this->session['contestUpload']['contestId'], $this->session['contestUpload']['mediaId'])) {
      $photoContestService = $this->getServiceLocator()->get('photoContestService');
      try {
        $contestId = $this->session['contestUpload']['contestId'];
        $contestMediaId = $photoContestService->addArtToContest($contestId, $this->session['contestUpload']['mediaId'], $this->session);
        $this->session['contestUpload'] = array('contestMediaId' => $contestMediaId);
      } catch (PhotoContestException $e) {
        $this->flashMessenger()->addErrorMessage($e->getMessage());
      }
      
      return $this->redirect()->toRoute('photo-contest', array(
          'id' =>  $contestId,
          'action' =>  'submission'
      ));
    }
    
    return $this->redirect()->toRoute('photo-contest');
  }

  public function uploadArtAction()
  {
    $this->checkLogin();
    $request = $this->getRequest();
    if ($request->isPost()) {
      $contestId = $request->getPost('contestId');
      if (!$contestId) {
        return new JsonModel(array(
            'success' => false,
            'message' => 'Bad Request'
        ));
      }

      $this->session->contestUpload = array(
          'contestId' => $contestId
      );
      return new JsonModel(array(
          'success' => true
      ));
    }

    return $this->redirect()->toRoute('photo-contest');
  }

  public function artSubmissionAction()
  {sleep(5);
    $this->checkLogin();

    $request = $this->getRequest();
    if ($request->isPost()) {
      $mediaId = $request->getPost('media_id');
      $contestId = $request->getPost('contestId');
      if (!$mediaId || !$contestId) {
        return new JsonModel(array(
            'success' => false,
            'message' => 'Bad Request'
        ));
      }

      $photoContestService = $this->getServiceLocator()->get('photoContestService');
      try {
        $contestMediaId = $photoContestService->addArtToContest($contestId, $mediaId, $this->session);
      } catch (PhotoContestException $e) {
        return new JsonModel(array(
            'success' => false,
            'message' => $e->getMessage()
        ));
      }

      return new JsonModel(array(
          'success' => true,
          'data' => array(
              'contestMediaId' => $contestMediaId
          )
      ));
    }

    return new JsonModel(array(
        'success' => false,
        'message' => 'Bad Request'
    ));
  }

  public function voteAction()
  {
    $this->checkLogin();

    $request = $this->getRequest();
    if ($request->isPost()) {
      $mediaId = $request->getPost('media_id');
      $contestId = $request->getPost('contestId');
      $rating = $request->getPost('rating');
      if (!$mediaId || !$contestId) {
        return new JsonModel(array(
            'success' => false,
            'message' => 'Bad Request'
        ));
      }

      $photoContestService = $this->getServiceLocator()->get('photoContestService');
      try {
        $contestMediaRatingId = $photoContestService->addVoteToArt($contestId, $mediaId, $this->session, $rating);
      } catch (PhotoContestException $e) {
        return new JsonModel(array(
            'success' => false,
            'message' => $e->getMessage()
        ));
      }

      return new JsonModel(array(
          'success' => true,
          'data' => array(
              'contestMediaRatingId' => $contestMediaRatingId
          )
      ));
    }

    return new JsonModel(array(
        'success' => false,
        'message' => 'Bad Request'
    ));
  }

  public function rankingAction()
  {
    
  }

  public function termsAction()
  {
    
  }

}
