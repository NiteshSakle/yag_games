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

        if (strtotime($this->contest['entry_start_date']) > strtotime(date('Y-m-d'))) {
            $this->flashMessenger()->addErrorMessage('Contest entry is not yet started');
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
        $userContestMedia = $contestMediaTable->getUserContestMediaCount($contestId, $this->session->mem_id);
        $fbscrap = $this->getServiceLocator()->get('fbScrapService');
        if ($userContestMedia) {
            $mediaId = $userContestMedia['media_id'];
            $showSubmitPopupDiv = 1;
            $fbscrap->informFbToScrap($contestId, $mediaId);
            $media = $contestMediaTable->getContestMediaDetails($userContestMedia['id']);
        }

        if (isset($this->session['contestUpload']['contestMediaId'])) {
            $media = $contestMediaTable->getContestMediaDetails($this->session['contestUpload']['contestMediaId']);
            unset($this->session['contestUpload']['contestMediaId']);
            $showPopupDiv = 1;
            $fbscrap->informFbToScrap($contestId, $media['media_id']);
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
                'contestId' => $contestId,
                'contestType' => 'brackets'
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
                
                if(!$contestMediaId) {
                    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
                    $contestMedia = $contestMediaTable->fetchContestMedia($contestId, $mediaId);
                    $contestMediaId = $contestMedia['id'];
                }
                
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
        } else {
            return new JsonModel(array(
                'success' => false,
                'message' => 'Bad Request'
            ));
        }
    }

    /*
     * Voting API's
     */

    public function votingAction()
    {
        $mediaId = $this->params()->fromRoute('mid') ? (int) $this->params()->fromRoute('mid') : 0;
        $contestId = $this->params()->fromRoute('id', null);
        $this->session = $this->sessionPlugin();
        $userId = '';
        $guestloggedIn = 0;
        if (isset($this->session->mem_id)) {
          $userId = $this->session->mem_id;
          $guestloggedIn = 1;
        } elseif (isset($_SESSION['guestUser']['guest_user_id'])) {
          $userId = $_SESSION['guestUser']['guest_user_id'];
          $guestloggedIn = 1;
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

        // check winner Announced are not if so redirect to winners announced page
        if (strtotime($this->contest['winners_announce_date']) <= strtotime(date("Y-m-d"))) {
            $this->flashMessenger()->addErrorMessage('Winners Announced');
            return $this->redirect()->toRoute('brackets', array(
                        'id' => $contestId,
                        'action' => 'rankings'
            ));
        }

        $media = 0;
        if ($mediaId) {
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
        $vm->setVariable('shareMedia', $media);
        $vm->setVariable('guestloggedIn', $guestloggedIn);
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

        $contestWinners = array();
        if (strtotime($this->contest['winners_announce_date']) <= strtotime(date("Y-m-d"))) {
            $ContestWinnerTable = $this->getServiceLocator()->get('YagGames\Model\ContestWinnerTable');
            $contestWinners = $ContestWinnerTable->fetchAllWinnersOfContest($contestId);
        }

        $media = 0;
        if ($mediaId) {
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
        $vm->setVariable('shareMedia', $media);
        $vm->setVariable('contestWinners', $contestWinners);

        return $vm;
    }

    public function getNextArtAction()
    {
        $contestId = $this->params()->fromQuery('contestId', null);
        $contestComboId = $this->params()->fromQuery('comboId', null);
        $round = $this->params()->fromQuery('round', null);
        $mediaId = $this->params()->fromQuery('mediaId', null);
        $this->session = $this->sessionPlugin();
        $this->getContest($contestId);
        if ($round == null || $round == 0) {
            $round = $this->contest['current_round'];
        }

        $media1 = array();
        $media2 = array();
        $noImages = 0;
        $previousRoundCheck = 1;
        $showThankq = 0;
        $contestData = array();
        if ($round != $this->contest['current_round'] && $mediaId) {
            $previousRound = $this->previousRoundImage($mediaId);
            if (!$previousRound['show_thankq']) {
                $round = $previousRound['round'];
                $contestComboId = $previousRound['combo_id'];
            } else {
                $previousRoundCheck = 0;
                $noImages = 1;
                $showThankq = 1;
                $contestData['contestDetails'] = 0;
            }
        }

        if ($previousRoundCheck) {
            if (isset($this->session->mem_id)) {
                $userId = $this->session->mem_id;
                $ratedMedia = array();
            } elseif(isset($_SESSION['guestUser']['guest_user_id'])) {
//                $userId = '';
//                $ratedMedia = $this->getRatedMedia($contestId, $round); //fill the data from cookie
                $userId = $_SESSION['guestUser']['guest_user_id'];
                $ratedMedia = array(); //fill the data from cookie
            } else {
                return $this->redirect()->toRoute('brackets', array(
                  'id' => $contestId,
                  'action' => 'voting'
                ));
            }

            $bracketService = $this->getServiceLocator()->get('bracketService');
            $contestData = $bracketService->getNextContestMedia($contestId, $userId, $contestComboId, $ratedMedia, $round);

            if ($contestData['contestDetails']) {
                if ($contestData['contestDetails']['contest_media_id1'] != 0) {
                    $media1 = $contestData['medias'][$contestData['contestDetails']['contest_media_id1']];
                }
                if ($contestData['contestDetails']['contest_media_id2'] != 0) {
                    $media2 = $contestData['medias'][$contestData['contestDetails']['contest_media_id2']];
                }
                $roundDetails = $this->getRoundNameAndCount($round);
                $contestData['count'] = $roundDetails['count'];
                $contestData['round_name'] = $roundDetails['round_name'];
                if ($contestData['contestDetails']['contest_media_id1'] == 0 && $contestData['contestDetails']['contest_media_id2'] == 0) {
                    $noImages = 1;
                }
            }

//            if (!isset($this->session->mem_id)) {
//                $contestData['totalRated'] = count($ratedMedia);
//            }
        }

        $viewModel = new ViewModel(array('media1' => $media1, 'media2' => $media2, 'contestId' => $contestId, 'contestData' => $contestData, 'noImages' => $noImages, 'contest' => $this->contest, 'showThankq' => $showThankq));

        return $viewModel->setTerminal(true);
    }

    public function voteAction()
    {
        $this->session = $this->sessionPlugin();
        $userId = '';
        if (isset($this->session->mem_id)) {
            $userId = $this->session->mem_id;
        } elseif(isset($_SESSION['guestUser']['guest_user_id'])) {
            $userId = $_SESSION['guestUser']['guest_user_id'];    
        } 

        $request = $this->getRequest();
        if ($request->isPost()) {
            $mediaId = $request->getPost('mediaId');
            $comboId = $request->getPost('comboId');
            $contestId = $request->getPost('contestId');
            $round = $request->getPost('round');
            if (!$mediaId || !$contestId || !$comboId || !$round) {
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
                $contestMediaRatingId = $bracketService->addVoteToArt($contestId, $mediaId, $userId, $comboId);
            } catch (BracketException $e) {
                return new JsonModel(array(
                    'success' => false,
                    'message' => $e->getMessage()
                ));
            }

            if (!$userId) {
                $this->storeRate($contestId, $comboId, $round);
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
        $this->getConfig();
        $KCrypt = $this->getServiceLocator()->get('kcryptService');
        $kcryptHelper = new \YagGames\View\Helper\KCryptHelper();
        $kcryptHelper->setKCryptService($KCrypt);
        $mediaUrl = $this->config['main_site']['url'] . '/photo/' . $kcryptHelper->enc($mediaId) . '/photo.html';

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

    private function getRoundNameAndCount($round)
    {
        $roundDetails = array();
        switch ($round) {
            case 1: $roundDetails['count'] = 32;
                $roundDetails['round_name'] = 'STARTING 64';
                break;
            case 2: $roundDetails['count'] = 16;
                $roundDetails['round_name'] = 'TOP 32';
                break;
            case 3: $roundDetails['count'] = 8;
                $roundDetails['round_name'] = 'SUPER 16';
                break;
            case 4: $roundDetails['count'] = 4;
                $roundDetails['round_name'] = 'GREAT 8';
                break;
            case 5: $roundDetails['count'] = 2;
                $roundDetails['round_name'] = 'CORE 4';
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

    private function previousRoundImage($mediaId)
    {
        $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
        $contestMediaData = (array) $contestMediaTable->fetchContestMedia($this->contest['id'], $mediaId);

        if (!$contestMediaData) {
            throw new \YagGames\Exception\BracketException("No contest media found");
        }

        $bracketMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketMediaComboTable');
        $bracketMediaData = $bracketMediaTable->fetchRecordByRoundAndMedia($this->contest['current_round'], $contestMediaData['id'], $this->contest['id']);

        if (!$bracketMediaData) {
            $bracketMediaData['show_thankq'] = 1;
        } else {
            $bracketMediaData['show_thankq'] = 0;
        }

        return $bracketMediaData;
    }

}
