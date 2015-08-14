<?php

namespace YagGames\Service;

class PhotoContestService
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
      throw new \YagGames\Exception\PhotoContestException("No contest found");
    }
    
    //only artist is allowed
    if ($userSession['login_as_buyer']) {
      throw new \YagGames\Exception\PhotoContestException("You have to be Artist to participate in contest");
    }
    
    //check end date
    $now = new \DateTime();
    $now = $now->format('Y-m-d');
    $endDate = new \DateTime($contestData['entry_end_date']);
    if ($now > $endDate) {
      throw new \YagGames\Exception\PhotoContestException("You cannot upload art as contest has already ended");
    }
    
    //max 200 in contest
    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
    $contestMediaCount = $contestMediaTable->getContestMediaCount($contestId, $userSession['mem_id']);
    if ($contestMediaCount && $contestMediaCount['count'] >= 200) {
      throw new \YagGames\Exception\PhotoContestException("Contest is already full");
    }
    
    //ONE PHOTO PER ARTIST
    if ($contestMediaCount && $contestMediaCount['has_uploaded']) {
      throw new \YagGames\Exception\PhotoContestException("You have already submitted art to this contest");
    }
    
    //is media owned by user?
    $mediaTable = $this->getServiceLocator()->get('YagGames\Model\MediaTable');
    $mediaObject = $mediaTable->fetchRecord($mediaId);
    if (!$mediaObject) {
      throw new \YagGames\Exception\PhotoContestException("No media found");
    }
    
    if ($mediaObject['owner'] != $userSession['mem_id']) {
      throw new \YagGames\Exception\PhotoContestException("Media is not owned by you", 403);
    }
    
    // now submit art
    $contestMedia = new \YagGames\Model\ContestMedia();
    $contestMedia->contest_id = $contestId;
    $contestMedia->media_id = $mediaId;
    
    $contestMediaId = $contestMediaTable->insert($contestMedia);   
    if (!$contestMediaId) {
      throw new \YagGames\Exception\PhotoContestException("Unable to submit art to contest");
    }
    
    return $contestMediaId;
    
  }

  public function addVoteToArt($contestId, $mediaId, $userSession, $rating)
  {
   
    $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
    $contestData = $contestTable->fetchRecord($contestId);
    $contestData = (array) $contestData;
    if (!$contestData) {
      throw new \YagGames\Exception\PhotoContestException("No contest found");
    }

    //check voting_started flag for contest
    if (!$contestData['voting_started']) {
      throw new \YagGames\Exception\PhotoContestException("Voting has not started");
    }
    
    //get contest & media id
    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
    $contestMediaData = $contestMediaTable->fetchContestMedia($contestId, $mediaId);
    $contestMediaData = (array) $contestMediaData;
    if (!$contestMediaData) {
      throw new \YagGames\Exception\PhotoContestException("No contest media found");
    }
    
    // Can rate once for a media in one day
    $contestMediaRatingTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaRatingTable');
    if (!empty($userSession['mem_id'])) {
      $count = $contestMediaRatingTable->hasAlreadyVotedForThisContestMediaToday($contestMediaData['id'], $userSession['mem_id']);
      if ($count) {
        throw new \YagGames\Exception\PhotoContestException("You have already voted fot this media in this contest today.");
      }
    }
            
    // now submit vote
    $contestMediaRating = new \YagGames\Model\ContestMediaRating();
    $contestMediaRating->contest_media_id = $contestMediaData['id'];
    $contestMediaRating->member_id = (!empty($userSession['mem_id'])) ? $userSession['mem_id'] : 0;
    $contestMediaRating->rating = $rating;
    $contestMediaRating->round = 0;
    
    $contestMediaRatingId = $contestMediaRatingTable->insert($contestMediaRating);   
    if (!$contestMediaRatingId) {
      throw new \YagGames\Exception\PhotoContestException("Unable to submit vote to contest");
    }
    
    return $contestMediaRatingId;
  }
  
  public function getContestMedia($contestId,  $userId = null, $keyword = null, $page = 1, $offset = 20, $sort = 'rank')
  {
    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
    $contestData = $contestMediaTable->getContestMedia($contestId, $userId, $keyword , $page, $offset, $sort);
    
    return $contestData;
  }
  
  public function getNextContestMedia($contestId,  $userId = null, $mediaId = null, $ratedMedia = array())
  {
    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
    $contestData = $contestMediaTable->getNextContestMedia($contestId, $userId, $mediaId, $ratedMedia);
    
    $count = 0;
    if ($userId) {
      $contestMediaRatingTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaRatingTable');
      $count = $contestMediaRatingTable->totalRatedForThisContestToday($contestId, $userId);
    }
    $contestData['totalRated'] = $count;
    
    return $contestData;
  }
  
  private function getServiceLocator()
  {
    return $this->serviceManager;
  }

}
