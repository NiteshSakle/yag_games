<?php

namespace YagGames\Model;

class MediaTable extends BaseTable
{

    public function fetchRecord($mediaId)
    {
        $rowset = $this->tableGateway->select(array('media_id' => $mediaId));
        $contestRow = $rowset->current();
        return $contestRow;
    }   
    
    public function fetchAll($userId)
    {
        $select = new \Zend\Db\Sql\Select ;
        $select->from(array('m' => 'ps4_media'))
                ->columns(array('*'))
                ->where('m.owner = ?', $userId);
         
        $statement = $this->getSql()->prepareStatementForSqlObject($select); 
        $resultSet = $statement->execute(); 
        
        return $resultSet;
    }
}
