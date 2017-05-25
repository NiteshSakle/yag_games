<?php

namespace YagGames\Model;
use Zend\Db\Sql\Expression;

class AdminActivityTrackTable extends BaseTable {
    
    public function saveAdminTracking($data, $isUpdate=FALSE)
    {
        $data['created_at'] =  new Expression('NOW()');
         
        if($isUpdate) {
            
            if($data['old_value']!='' && $data['new_value']!="" && $data['old_value'] != $data['new_value']) {
                $this->tableGateway->insert($data);
            }
            
        } else {
            $this->tableGateway->insert($data);
            
            return TRUE;
        }
    }
}