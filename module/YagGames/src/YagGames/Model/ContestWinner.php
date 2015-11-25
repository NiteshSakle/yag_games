<?php

namespace YagGames\Model;

class ContestWinner extends BaseModel
{
    public $id;
    public $contest_media_id;
    public $rank;
    public $no_of_votes;
    public $created_at;
    public $updated_at;

}