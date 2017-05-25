<?php

namespace YagGames\Model;

class AdminActivityTrack extends BaseModel
{
    public $id;
    public $admin_id;
    public $comment;
    public $form_name;
    public $change_type;
    public $created_at;
    public $updated_at;

}