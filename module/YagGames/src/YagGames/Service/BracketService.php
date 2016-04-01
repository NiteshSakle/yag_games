<?php

namespace YagGames\Service;

class BracketService
{

  private $serviceManager;

  public function __construct($serviceManager)
  {
    $this->serviceManager = $serviceManager;
  }

  public function addArtToContest($contestId, $mediaId, $userSession)
  {
    $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
    $contestData = $contestTable->fetchRecord($contestId);
    $contestData = (array) $contestData;
    if (!$contestData) {
      throw new \YagGames\Exception\BracketException("No contest found");
    }
    
    //only artist is allowed
    if ($userSession['login_as_buyer']) {
      throw new \YagGames\Exception\BracketException("You have to be Artist to participate in contest");
    }
    
    //check end date
    $now = new \DateTime();
    $now = $now->format('Y-m-d');
    $endDate = $contestData['entry_end_date'];
    $startDate = $contestData['entry_start_date'];

    //check start date
    $startDate = new \DateTime($contestData['entry_start_date']);
    $startDate = $startDate->format('Y-m-d');
    
    if ($startDate > $now) {
      throw new \YagGames\Exception\PhotoContestException("Contest entry is not yet started");
    }
    
    //check end date
    $endDate = new \DateTime($contestData['entry_end_date']);
    $endDate = $endDate->format('Y-m-d');
    if ($now > $endDate) {
      throw new \YagGames\Exception\PhotoContestException("You cannot upload art as contest has already ended");
    }  
    
    //max 200 in contest
    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
    $contestMediaCount = $contestMediaTable->getContestMediaCount($contestId, $userSession['mem_id']);
    if ($contestMediaCount && $contestMediaCount['count'] >= 64) {
      throw new \YagGames\Exception\BracketException("Contest is already full");
    }
    
    //ONE PHOTO PER ARTIST
    if ($contestMediaCount && $contestMediaCount['has_uploaded']) {
      throw new \YagGames\Exception\BracketException("You have already submitted art to this contest");
    }
    
    //is media owned by user?
    $mediaTable = $this->getServiceLocator()->get('YagGames\Model\MediaTable');
    $mediaObject = $mediaTable->fetchRecord($mediaId);
    if (!$mediaObject) {
      throw new \YagGames\Exception\BracketException("No media found");
    }
    
    if ($mediaObject['owner'] != $userSession['mem_id']) {
      throw new \YagGames\Exception\BracketException("Media is not owned by you", 403);
    }
    
    // now submit art
    $contestMedia = new \YagGames\Model\ContestMedia();
    $contestMedia->contest_id = $contestId;
    $contestMedia->media_id = $mediaId;
    
    $contestMediaId = $contestMediaTable->insert($contestMedia);   
    if (!$contestMediaId) {
      throw new \YagGames\Exception\BracketException("Unable to submit art to contest");
    }
    
    return $contestMediaId;
    
  }

  public function addVoteToArt($contestId, $mediaId, $userSession, $comboId)
  {
    $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
    $contestData = $contestTable->fetchRecord($contestId);
    $contestData = (array) $contestData;
    if (!$contestData) {
      throw new \YagGames\Exception\BracketException("No contest found");
    }
    
    //check voting_started flag for contest
    if (!$contestData['voting_started']) {
      throw new \YagGames\Exception\BracketException("Voting has not started");
    }
    
    //get contest & media id fetchRecordByRoundAndMedia
    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
    $contestMediaData = $contestMediaTable->fetchContestMedia($contestId, $mediaId);
    $contestMediaData = (array) $contestMediaData;
    if (!$contestMediaData) {
      throw new \YagGames\Exception\BracketException("No contest media found");
    }
    
    // Can rate once for a media in one round
    $contestMediaRatingTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaRatingTable');
    if (!empty($userSession['mem_id'])) {
      $count = $contestMediaRatingTable->hasAlreadyVotedForThisBracketContest($contestData['current_round'], $comboId, $userSession['mem_id'], $contestData['id']);
      if ($count) {
        throw new \YagGames\Exception\BracketException("You have already voted for this combo in this Round");
      }
    }
     
    $clientIPService = $this->getServiceLocator()->get('clientIPService');            
    $clientIP = $clientIPService->getClientIPAddress();
    
    // now submit vote
    $contestMediaRating = new \YagGames\Model\ContestMediaRating();
    $contestMediaRating->contest_media_id = $contestMediaData['id'];
    $contestMediaRating->member_id = (!empty($userSession['mem_id'])) ? $userSession['mem_id'] : 0;
    $contestMediaRating->rating = 10;
    $contestMediaRating->round = $contestData['current_round'];
    $contestMediaRating->bracket_combo_id = $comboId;
    $contestMediaRating->ip_address = $clientIP;
    
    $contestMediaRatingId = $contestMediaRatingTable->insert($contestMediaRating);   
    if (!$contestMediaRatingId) {
      throw new \YagGames\Exception\BracketException("Unable to submit vote to contest");
    }
    
    return $contestMediaRatingId;
  }
  
  public function getContestMedia($contestId,  $userId = null)
  {
    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
    $contestData = $contestMediaTable->fetchBracketContestMedia($contestId, $userId);
    
    return $contestData;
  }
  
  public function getNextContestMedia($contestId,  $userId = null, $contestComboId = null, $ratedMedia = array(), $round)
  {
    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
    $config = $this->getServiceLocator()->get('Config');  
    $clientIPService = $this->getServiceLocator()->get('clientIPService');      //Get IP Address      
    $clientIP = $clientIPService->getClientIPAddress();
    $contestData = $contestMediaTable->getNextBracketMedia($contestId, $userId, $contestComboId, $ratedMedia, $round, $config['white_listed_ips'], $clientIP);
    
    $count = 0;
    if ($userId) {
      $contestMediaRatingTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaRatingTable');
      $count = $contestMediaRatingTable->totalRatedForThisBracketRound($contestId, $userId, $round);
    }
    $contestData['totalRated'] = $count;
    
    return $contestData;
  }
  
  private function getServiceLocator()
  {
    return $this->serviceManager;
  }

}
