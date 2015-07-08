<?php

namespace YagGames\Model;

class ContestBracketRound extends BaseModel
{
    public $id;
    public $contest_id;
    public $round1;
    public $round2;
    public $round3;
    public $round4;
    public $round5;
    public $created_at;
    public $updated_at;

}