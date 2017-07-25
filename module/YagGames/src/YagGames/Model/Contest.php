<?php

namespace YagGames\Model;

class Contest extends BaseModel
{
    public $id;
    public $name;
    public $description;
    public $thumbnail;
    public $publish_contest;
    public $entry_start_date;
    public $entry_end_date;
    public $winners_announce_date;
    public $voting_start_date;
    public $max_no_of_photos;
    public $voting_started;
    public $winners_announced;
    public $announce_winners_under_process;
    public $is_exclusive;
    public $type_id;
    public $created_at;
    public $updated_at;

}