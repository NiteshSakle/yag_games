<?php

namespace YagGames\Model;

class ContestRankingsProcessed extends BaseModel
{
    public $id;
    public $contest_rankings_modify_id;    
    public $before_rank;
    public $after_rank;
    public $processed;
    public $comments;
    public $created_at;    
}
