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

  /*
   * Submission API's 
   */

  public function submissionAction()
  {
    $this->checkLogin();

    $contestId = $this->params()->fromRoute('id', null);
    if (!$this->getContest($contestId)) {
      $this->flashMessenger()->addErrorMessage('No contest found');
      return $this->redirect()->toRoute('home');
    }

    if ($this->contest['voting_started']) {
      $this->flashMessenger()->addErrorMessage('Voting has already started');

      return $this->redirect()->toRoute('photo-contest', array(
                  'id' => $contestId,
                  'action' => 'voting'
      ));
    }

    $showPopupDiv = 0;
    $showSubmitPopupDiv = 0;
    $media = array();
    $mediaId = 0;
    
    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
    $userContestMedia = $contestMediaTable->getUserContestMediaCount($contestId, $this->session->mem_id );
    if($userContestMedia){
        $mediaId = $userContestMedia['media_id'];
        $showSubmitPopupDiv = 1;
    }
    
    if (isset($this->session['contestUpload']['contestMediaId'])) {      
      $media = $contestMediaTable->getContestMediaDetails($this->session['contestUpload']['contestMediaId']);
      unset($this->session['contestUpload']['contestMediaId']);
      $showPopupDiv = 1;
    }

    return new ViewModel(array('contest' => $this->contest, 'showPopupDiv' => $showPopupDiv, 'media' => $media, 'mediaId' => $mediaId, 'showSubmitPopupDiv' => $showSubmitPopupDiv));
  }

  public function uploadSubmissionAction()
  {
    $this->checkLogin();
    if (isset($this->session['contestUpload']['contestId'], $this->session['contestUpload']['mediaId'])) {
      $photoContestService = $this->getServiceLocator()->get('photoContestService');
      try {
        $contestId = $this->session['contestUpload']['contestId'];
        $contestMediaId = $photoContestService->addArtToContest($contestId, $this->session['contestUpload']['mediaId'], $this->session);

        $process = new \YagGames\Utils\Process($this->getRequest());
        $process->start('SendSuccessSubmissionEmail ' . $contestMediaId);

        $this->session['contestUpload'] = array('contestMediaId' => $contestMediaId);
      } catch (PhotoContestException $e) {
        $this->flashMessenger()->addErrorMessage($e->getMessage());
      }

      return $this->redirect()->toRoute('photo-contest', array(
                  'id' => $contestId,
                  'action' => 'submission'
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
  {
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

        $process = new \YagGames\Utils\Process($request);
        $process->start('SendSuccessSubmissionEmail ' . $contestMediaId);
      } catch (PhotoContestException $e) {
        return new JsonModel(array(
            'success' => false,
            'message' => $e->getMessage()
        ));
      }

      return new JsonModel(array(
          'success' => true,
          'data' => array(
              'contestMediaId' => $contestMediaId,
              'mediaId' => $mediaId,
              'contestId' => $contestId
          )
      ));
    }

    return new JsonModel(array(
        'success' => false,
        'message' => 'Bad Request'
    ));
  }

  /*
   * Voting API's
   */

  public function votingAction()
  {
    $page = $this->params()->fromRoute('page') ? (int) $this->params()->fromRoute('page') : 1;
    $size = $this->params()->fromRoute('size') ? (int) $this->params()->fromRoute('size') : 20;
    $contestId = $this->params()->fromRoute('id', null);
    $search = $this->params()->fromPost('search', null);
    $this->session = $this->sessionPlugin();
    $userId = '';
    if (isset($this->session->mem_id)) {
      $userId = $this->session->mem_id;
    }

    //get contest details
    if (!$this->getContest($contestId)) {
      $this->flashMessenger()->addErrorMessage('No contest found');
      return $this->redirect()->toRoute('home');
    }
    
    // check voting started are not
    if (!$this->contest['voting_started']) {
      $this->flashMessenger()->addErrorMessage('Voting hasn\'t started yet');
      return $this->redirect()->toRoute('photo-contest');
    }

    $photoContestService = $this->getServiceLocator()->get('photoContestService');
    $data = $photoContestService->getContestMedia($contestId, $userId, $search, $page, $size, '');

    $paginator = new Paginator(new NullFill($data['total']));
    $paginator->setCurrentPageNumber($page);
    $paginator->setItemCountPerPage($size);

    $vm = new ViewModel();
    $vm->setVariable('paginator', $paginator);
    $vm->setVariable('medias', $data['medias']);
    $vm->setVariable('contestId', $contestId);
    $vm->setVariable('search', $search);
    $vm->setVariable('page', $page);
    $vm->setVariable('size', $size);
    return $vm;
  }

  public function learnMoreAction()
  {
    
  }

  public function rankingsAction()
  {
    $page = $this->params()->fromRoute('page') ? (int) $this->params()->fromRoute('page') : 1;
    $size = $this->params()->fromRoute('size') ? (int) $this->params()->fromRoute('size') : 20;
    $contestId = $this->params()->fromRoute('id', null);
    $this->session = $this->sessionPlugin();
    $userId = '';
    if (isset($this->session->mem_id)) {
      $userId = $this->session->mem_id;
    }

    //get contest details
    if (!$this->getContest($contestId)) {
      $this->flashMessenger()->addErrorMessage('No contest found');
      return $this->redirect()->toRoute('home');
    }
    
    // check voting started are not
    if (!$this->contest['voting_started']) {
      $this->flashMessenger()->addErrorMessage('Voting hasn\'t started yet');
      return $this->redirect()->toRoute('photo-contest');
    }

    $photoContestService = $this->getServiceLocator()->get('photoContestService');
    $data = $photoContestService->getContestMedia($contestId, $userId, null, $page, $size, 'rank');

    $paginator = new Paginator(new NullFill($data['total']));
    $paginator->setCurrentPageNumber($page);
    $paginator->setItemCountPerPage($size);

    $vm = new ViewModel();
    $vm->setVariable('paginator', $paginator);
    $vm->setVariable('medias', $data['medias']);
    $vm->setVariable('contestId', $contestId);
    $vm->setVariable('contest', $this->contest);
    $vm->setVariable('page', $page);
    $vm->setVariable('size', $size);
    return $vm;
  }

  public function getNextArtAction()
  {
    $contestId = $this->params()->fromQuery('contestId', null);
    $mediaId = $this->params()->fromQuery('mediaId', null);
    $this->session = $this->sessionPlugin();

    if (isset($this->session->mem_id)) {
      $userId = $this->session->mem_id;
      $ratedMedia = array();
    } else {
      $userId = '';
      $ratedMedia = $this->getRatedMedia($contestId); //fill the data from cookie
    }

    $photoContestService = $this->getServiceLocator()->get('photoContestService');
    $media = $photoContestService->getNextContestMedia($contestId, $userId, $mediaId, $ratedMedia);
    if (!isset($this->session->mem_id)) {
      $media['totalRated'] = count($ratedMedia);
    }

    $viewModel = new ViewModel(array('media' => $media, 'contestId' => $contestId, 'mediaId' => $mediaId));

    return $viewModel->setTerminal(true);
  }

  public function voteAction()
  {
    $this->session = $this->sessionPlugin();
    $userId = '';
    if (isset($this->session->mem_id)) {
      $userId = $this->session->mem_id;
    }

    $request = $this->getRequest();
    if ($request->isPost()) {
      $mediaId = $request->getPost('mediaId');
      $contestId = $request->getPost('contestId');
      $rating = $request->getPost('rating');
      if (!$mediaId || !$contestId) {
        return new JsonModel(array(
            'success' => false,
            'message' => 'Bad Request'
        ));
      }

      // for non logged in user
      // check user already rated the contest media from this browser
      if (!$userId) {
        $resp = $this->isAlreadyRated($contestId, $mediaId);
        if ($resp) {
          return new JsonModel(array(
              'success' => false,
              'message' => 'You have already rated'
          ));
        }
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

      if (!$userId) {
        $this->storeRate($contestId, $mediaId);
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

  private function storeRate($contestId, $mediaId)
  {
    try {
      //store in cookie
      $rmArray = $this->getRmCookie();

      if (!isset($rmArray[$contestId])) {
        $rmArray[$contestId] = array();
      }
      $rmArray[$contestId][] = $mediaId;

      $cookie = new \Zend\Http\Header\SetCookie('rm', \json_encode($rmArray), mktime(24, 0, 0), '/');
      $this->getResponse()->getHeaders()->addHeader($cookie);
    } catch (\Exception $e) {
      $this->getServiceLocator()->get('YagGames\Logger')->err($e->getMessage());
    }
  }

  private function isAlreadyRated($contestId, $mediaId)
  {
    try {
      $rmArray = $this->getRmCookie();

      if (isset($rmArray[$contestId][$mediaId])) {
        return true;
      }
    } catch (\Exception $e) {
      $this->getServiceLocator()->get('YagGames\Logger')->err($e->getMessage());
    }

    return false;
  }

  private function getRatedMedia($contestId)
  {
    try {
      $rmArray = $this->getRmCookie();

      if (isset($rmArray[$contestId])) {
        return $rmArray[$contestId];
      }
    } catch (\Exception $e) {
      $this->getServiceLocator()->get('YagGames\Logger')->err($e->getMessage());
    }
    return array();
  }

  private function getRmCookie()
  {
    $rmArray = "";
    if (isset($this->getRequest()->getCookie()->rm)) {
      $rmString = $this->getRequest()->getCookie()->rm;
      $rmArray = \json_decode($rmString, true);
    }

    return $rmArray;
  }

  private function getContest($contestId)
  {
    $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
    $this->contest = $contestTable->fetchRecord($contestId);
    if ($this->contest) {
      $this->contest = (array) $this->contest;
    } else {
      $this->contest = array();
    }

    return $this->contest;
  }

}