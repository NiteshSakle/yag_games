<?php

namespace YagGames\Service;

class FanFavoriteService
{

  private $serviceManager;

  public function __construct($serviceManager)
  {
    $this->serviceManager = $serviceManager;
  }

  public function addArtToContest($contestId, $userId, $mediaId)
  {
    //only artist is allowed
    //check end date
    //ONE PHOTO PER ARTIST
    //max 200 in contest
  }

  public function addVoteToArt($contestId, $userId, $mediaId)
  {
    //check end date/votin_started flag
    //ONE VOTE PER PHOTO PER DAY
    //Artist/Buyer/Guest  can vote
  }

}
