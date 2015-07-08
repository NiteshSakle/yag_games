<?php

namespace YagGames\Model;

class ContestMediaRatingTable extends BaseTable
{

    public function insert(ContestMediaRating $contestMediaRating)
    {
        try {
            
            $this->created($contestMediaRating);
            if (!$this->isValid($contestMediaRating)) {
                return false;
            }
            $this->tableGateway->insert($contestMediaRating->getArrayCopy());
            $filedId = $this->tableGateway->getLastInsertValue();
            return $filedId;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function update(ContestMediaRating $contestMediaRating)
    {
        try {
            $this->updated($contestMediaRating);
            if (!$this->isValid($contestMediaRating)) {
                return false;
            }
            $this->tableGateway->update($contestMediaRating->getArrayCopy());
            return true;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function fetchRecord($contestMediaRatingId)
    {
        $rowset = $this->tableGateway->select(array('id' => $contestMediaRatingId));
        $contestRow = $rowset->current();
        return $contestRow;
    }   
    
    public function fetchAll()
    {
        $select = new \Zend\Db\Sql\Select ;
        $select->from(array('c' => 'contest_media_rating'))
                ->columns(array('*'));
         
        $statement = $this->getSql()->prepareStatementForSqlObject($select); 
        $resultSet = $statement->execute(); 
        
        return $resultSet;
    }
}
