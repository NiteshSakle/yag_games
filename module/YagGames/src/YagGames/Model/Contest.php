<?php

namespace YagGames\Model;

class Contest extends BaseModel
{
    public $id;
    public $name;
    public $description;
    public $thumbnail;
    public $entry_end_date;
    public $winners_announce_date;
    public $voting_start_date;
    public $max_no_of_photos;
    public $voting_started;
    public $is_exclusive;
    public $type_id;
    public $created_at;
    public $updated_at;

}