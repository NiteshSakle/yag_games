<?php

namespace YagGames\Model;

class ContestRankingsModify extends BaseModel
{
    public $id;
    public $admin_id;
    public $contest_media_id;
    public $intended_rank;
    public $status;
    public $created_at;    
}