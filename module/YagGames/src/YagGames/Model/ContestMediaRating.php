<?php

namespace YagGames\Model;

class ContestMediaRating extends BaseModel
{
    public $id;
    public $contest_media_id;
    public $member_id;
    public $round;
    public $rating;    
    public $created_at;
    public $updated_at;

}