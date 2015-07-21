<?php

namespace YagGames\Service;

class PhotoContestService
{

  private $serviceManager;

  public function __construct($serviceManager)
  {
    $this->serviceManager = $serviceManager;
  }

  public function addArtToContest($contestId, $userId, $mediaId)
  {
    $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
    $contestData = $contestTable->fetchRecord($contestId);
    if (!$contestData) {
      throw new \YagGames\Exception\PhotoContestException("No contest found");
    }
    
    //only artist is allowed
    if ($contestData['login_as_buyer']) {
      throw new \YagGames\Exception\PhotoContestException("You have to be Artist to participate in contest");
    }
    
    //check end date
    $now = new DateTime();
    $endDate = new DateTime($contestData['entry_end_date']);
    if ($now > $endDate) {
      throw new \YagGames\Exception\PhotoContestException("You cannot upload art as contest has already ended");
    }
    
    //max 200 in contest
    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
    $contestMediaCount = $contestMediaTable->getContestMediaCount($contestId, $userId);
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
    
    if ($mediaObject['owner'] != $userId) {
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

  public function addVoteToArt($contestId, $mediaId, $userId, $rating)
  {
   
    $contestTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
    $contestData = $contestTable->fetchRecord($contestId);
    if (!$contestData) {
      throw new \YagGames\Exception\PhotoContestException("No contest found");
    }
    
     //check end date/voting_started flag for contest
    $now = new DateTime();
    $endDate = new DateTime($contestData['entry_end_date']);
    if ($now <= $endDate || !$contestData['voting_started']) {
      throw new \YagGames\Exception\PhotoContestException("Voting has not started");
    }
    
    //get contest & media id
    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
    $contestMediaData = $contestMediaTable->fetchContestMedia($contestId, $mediaId);
    if (!$contestMediaData) {
      throw new \YagGames\Exception\PhotoContestException("No contest media found");
    }
    
     // FOR ONE CONTEST
    $contestMediaRatingTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaRatingTable');
    $count = $contestMediaRatingTable->hasAlreadyVotedForThisContestMedia($contestMediaData['id']);
    if ($count) {
      throw new \YagGames\Exception\PhotoContestException("You have already voted fot this media in this contest.");
    }
            
    // now submit vote
    $contestMediaRating = new \YagGames\Model\ContestMediaRating();
    $contestMediaRating->contest_media_id = $contestMediaData['id'];
    $contestMediaRating->member_id = $userId;
    $contestMediaRating->rating = $rating;
    
    $contestMediaRatingId = $contestMediaRatingTable->insert($contestMediaRating);   
    if (!$contestMediaRatingId) {
      throw new \YagGames\Exception\PhotoContestException("Unable to submit vote to contest");
    }
    
    return $contestMediaRatingId;
  }
  
  public function getContestMedia($contestId,  $userId = null, $keyword = null, $page = 1, $offset = 20)
  {
    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
    $contestData = $contestMediaTable->getContestMedia($contestId, $userId = null, $keyword = null, $page = 1, $offset = 20);
    
    return $contestData;
  }
  
  private function getServiceLocator()
  {
    return $this->serviceManager;
  }

}
