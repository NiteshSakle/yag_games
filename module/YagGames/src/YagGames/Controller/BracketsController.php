<?php

namespace YagGames\Controller;

use Exception;
use YagGames\Exception\BracketException;
use Zend\Mvc\MvcEvent;
use Zend\Paginator\Adapter\NullFill;
use Zend\Paginator\Paginator;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class BracketsController extends BaseController
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

      return $this->redirect()->toRoute('brackets', array(
                  'id' => $contestId,
                  'action' => 'voting'
      ));
    }
    
    if ($this->contest['is_exclusive'] && $this->session->membership != 4) {
        $this->flashMessenger()->addErrorMessage('Please Upgrade your account to participate in exclusive contest');
        return $this->redirect()->toRoute('home');        
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
      $bracketService = $this->getServiceLocator()->get('bracketService');
      try {
        $contestId = $this->session['contestUpload']['contestId'];
        $contestMediaId = $bracketService->addArtToContest($contestId, $this->session['contestUpload']['mediaId'], $this->session);

        $process = new \YagGames\Utils\Process($this->getRequest());
        $process->start('SendSuccessSubmissionEmail ' . $contestMediaId);

        $this->session['contestUpload'] = array('contestMediaId' => $contestMediaId);
      } catch (BracketException $e) {
        $this->flashMessenger()->addErrorMessage($e->getMessage());
      }

      return $this->redirect()->toRoute('brackets', array(
                  'id' => $contestId,
                  'action' => 'submission'
      ));
    }

    return $this->redirect()->toRoute('brackets');
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

    return $this->redirect()->toRoute('brackets');
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

      $bracketService = $this->getServiceLocator()->get('bracketService');
      try {
        $contestMediaId = $bracketService->addArtToContest($contestId, $mediaId, $this->session);

        $process = new \YagGames\Utils\Process($request);
        $process->start('SendSuccessSubmissionEmail ' . $contestMediaId);
      } catch (BracketException $e) {
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
      return $this->redirect()->toRoute('brackets');
    }

    $bracketService = $this->getServiceLocator()->get('bracketService');
    $data = $bracketService->getContestMedia($contestId, $userId);
    
    $contestBracketMediaComboTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketMediaComboTable');
    $contestComboDetails = $contestBracketMediaComboTable->fetchContestComboDetails($contestId);
    
    $vm = new ViewModel();
    $vm->setVariable('medias', $data['medias']);
    $vm->setVariable('contestId', $contestId);
    $vm->setVariable('contest', $this->contest);
    $vm->setVariable('comboDetails', $contestComboDetails);
    return $vm;
  }

  public function learnMoreAction()
  {
    
  }

  public function rankingsAction()
  {
    $mediaId = $this->params()->fromRoute('mid') ? (int) $this->params()->fromRoute('mid') : 0;
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
      return $this->redirect()->toRoute('brackets');
    }
    $media = 0;
    if($mediaId) { 
      $mediaTable = $this->getServiceLocator()->get('YagGames\Model\MediaTable');
      $media = $mediaTable->fetchRecord($mediaId);
    }
    
    $bracketService = $this->getServiceLocator()->get('bracketService');
    $data = $bracketService->getContestMedia($contestId, $userId);

    $contestBracketMediaComboTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketMediaComboTable');
    $contestComboDetails = $contestBracketMediaComboTable->fetchContestComboDetails($contestId);
    
    $vm = new ViewModel();
    $vm->setVariable('medias', $data['medias']);
    $vm->setVariable('contestId', $contestId);
    $vm->setVariable('contest', $this->contest);
    $vm->setVariable('comboDetails', $contestComboDetails);
    $vm->setVariable('media', $media);
    return $vm;
  }

  public function getNextArtAction()
  {
    $contestId = $this->params()->fromQuery('contestId', null);
    $mediaId = $this->params()->fromQuery('mediaId', null);
    $round = $this->params()->fromQuery('round', null);
    $this->session = $this->sessionPlugin();

    if (isset($this->session->mem_id)) {
      $userId = $this->session->mem_id;
      $ratedMedia = array();
    } else {
      $userId = '';
      $ratedMedia = $this->getRatedMedia($contestId, $round); //fill the data from cookie
    }
    $contestMediaId = Null;
    if($mediaId) {
        $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
        $contestMediaData = (array) $contestMediaTable->fetchContestMedia($contestId,$mediaId);

        if (!$contestMediaData) {
          throw new \YagGames\Exception\BracketException("No contest media found");
        }
        $contestMediaId = $contestMediaData['id'];
    }
    
    $bracketService = $this->getServiceLocator()->get('bracketService');
    $contestData = $bracketService->getNextContestMedia($contestId, $userId, $contestMediaId, $ratedMedia, $round);
    
    $media1=array();
    $media2=array();
    if($contestData['contestDetails']){
        $media1 = $contestData['medias'][$contestData['contestDetails']['contest_media_id1']];
        $media2 = $contestData['medias'][$contestData['contestDetails']['contest_media_id2']];
        $roundDetails = $this->getRoundNameAndCount($round);
        $contestData['count'] = $roundDetails['count'];
        $contestData['round_name'] = $roundDetails['round_name'];
    }
    
    if (!isset($this->session->mem_id)) {
        $contestData['totalRated'] = count($ratedMedia);
    }
    
    $viewModel = new ViewModel(array('media1' => $media1, 'media2' => $media2 , 'contestId' => $contestId, 'mediaId' => $mediaId, 'contestData' => $contestData));

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
      $comboId = $request->getPost('comboId');
      $contestId = $request->getPost('contestId');
      $round = $request->getPost('round');
      if (!$mediaId || !$contestId || !$comboId|| !$round) {
        return new JsonModel(array(
            'success' => false,
            'message' => 'Bad Request'
        ));
      }

      // for non logged in user
      // check user already rated the contest media from this browser
      if (!$userId) {
        $resp = $this->isAlreadyRated($contestId, $comboId, $round);
        if ($resp) {
          return new JsonModel(array(
              'success' => false,
              'message' => 'You have already rated'
          ));
        }
      }

      $bracketService = $this->getServiceLocator()->get('bracketService');
      try {
        $contestMediaRatingId = $bracketService->addVoteToArt($contestId, $mediaId, $this->session, $comboId);
      } catch (BracketException $e) {
        return new JsonModel(array(
            'success' => false,
            'message' => $e->getMessage()
        ));
      }

      if (!$userId) {
        $this->storeRate($contestId, $comboId,$round);
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
  
  public function getImageUrlAction() 
  {      
    $request = $this->getRequest();    
    if ($request->isPost()) {
      $mediaId = $request->getPost('mediaId');
    }
    $config = $this->getServiceLocator()->get('config');
    $KCrypt = $this->getServiceLocator()->get('kcryptService');
    $kcryptHelper = new \YagGames\View\Helper\KCryptHelper();
    $kcryptHelper->setKCryptService($KCrypt);    
    $mediaUrl = $config['main_site']['url'] . '/photo/' . $kcryptHelper->enc($mediaId) . '/photo.html' ;
    
    return new JsonModel(array(
        'success' => true,
        'mediaUrl' => $mediaUrl
    ));
  }

  private function storeRate($contestId, $comboId, $round)
  {
    try {
      //store in cookie
      $rmArray = $this->getRmCookie();

      if (!isset($rmArray[$contestId][$round])) {
        $rmArray[$contestId][$round] = array();
      }
      $rmArray[$contestId][$round][$comboId] = $comboId;

      $cookie = new \Zend\Http\Header\SetCookie('rm', \json_encode($rmArray), time() + 30 * 60 * 60 * 24, '/');
      $this->getResponse()->getHeaders()->addHeader($cookie);
    } catch (\Exception $e) {
      $this->getServiceLocator()->get('YagGames\Logger')->err($e->getMessage());
    }
  }

  private function isAlreadyRated($contestId, $comboId, $round)
  {
    try {
      $rmArray = $this->getRmCookie();

      if (isset($rmArray[$contestId][$round][$comboId])) {
        return true;
      }
    } catch (\Exception $e) {
      $this->getServiceLocator()->get('YagGames\Logger')->err($e->getMessage());
    }

    return false;
  }

  private function getRatedMedia($contestId, $round)
  {
    try {
      $rmArray = $this->getRmCookie();

      if (isset($rmArray[$contestId][$round])) {
        return $rmArray[$contestId][$round];
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
  private function getRoundNameAndCount($round) {
      $roundDetails = array();
      switch ($round){
          case 1: $roundDetails['count'] = 32;
              $roundDetails['round_name'] = 'STARTING 64';
              break;
          case 2: $roundDetails['count'] = 16;
              $roundDetails['round_name'] = 'TOP 64';
              break;
          case 3: $roundDetails['count'] = 8;
              $roundDetails['round_name'] = 'SWEET 16';
              break;
          case 4: $roundDetails['count'] = 4;
              $roundDetails['round_name'] = 'Elite 8';
              break;
          case 5: $roundDetails['count'] = 2;
              $roundDetails['round_name'] = 'Final 4';
              break;
          case 6: $roundDetails['count'] = 1;
              $roundDetails['round_name'] = 'SEMI-FINAL';
              break;
          
          default: $roundDetails['count'] = 0;
              $roundDetails['round_name'] = 'Error';
              break;
          
      }
      return $roundDetails;
  }

}
