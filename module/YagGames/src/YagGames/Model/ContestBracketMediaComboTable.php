<?php

namespace YagGames\Model;

class ContestBracketMediaComboTable extends BaseTable
{

    public function insert(ContestBracketMediaCombo $contestBracketMediaCombo)
    {
        try {
            
            $this->created($contestBracketMediaCombo);
            if (!$this->isValid($contestBracketMediaCombo)) {
                return false;
            }
            $this->tableGateway->insert($contestBracketMediaCombo->getArrayCopy());
            $filedId = $this->tableGateway->getLastInsertValue();
            return $filedId;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function update(ContestBracketMediaCombo $contestBracketMediaCombo)
    {
        try {
            $this->updated($contestBracketMediaCombo);
            if (!$this->isValid($contestBracketMediaCombo)) {
                return false;
            }
            $this->tableGateway->update($contestBracketMediaCombo->getArrayCopy());
            return true;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function fetchRecord($contestBracketMediaComboId)
    {
        $rowset = $this->tableGateway->select(array('id' => $contestBracketMediaComboId));
        $contestRow = $rowset->current();
        return $contestRow;
    }   
    
    public function fetchAll()
    {
        $select = new \Zend\Db\Sql\Select ;
        $select->from(array('c' => 'contest_bracket_media_combo'))
                ->columns(array('*'));
         
        $statement = $this->getSql()->prepareStatementForSqlObject($select); 
        $resultSet = $statement->execute(); 
        
        return $resultSet;
    }
    
    public function fetchRecordByRoundAndMedia($round, $mediaId, $contestId)
    {
        $select = new \Zend\Db\Sql\Select ;
        $select->from(array('c' => 'contest_bracket_media_combo'))
                ->columns(array('*'))
                ->where(array(
                    'c.round' => $round,
                    'c.contest_id' => $contestId));
        $select->where
                ->NEST
                ->equalTo('c.contest_media_id1', $mediaId)
                ->OR
                ->equalTo('c.contest_media_id2', $mediaId)
                ->UNNEST;                
         
        $statement = $this->getSql()->prepareStatementForSqlObject($select); 
        $resultSet = $statement->execute(); 
        
        return $resultSet;;
    } 
}
