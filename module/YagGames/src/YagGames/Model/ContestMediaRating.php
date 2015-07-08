<?php

namespace YagGames\Model;

class ContestMediaRating extends BaseModel
{
    public $id;
    public $contest_id;
    public $round;
    public $media_id;
    public $rating;
    public $member_id;
    public $created_at;
    public $updated_at;

}