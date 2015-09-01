<?php

namespace YagGames\Model;

class ContestBracketRoundTable extends BaseTable
{

    public function insert(ContestBracketRound $contestBracketRound)
    {
        try {
            
            $this->created($contestBracketRound);
            if (!$this->isValid($contestBracketRound)) {
                return false;
            }
            $this->tableGateway->insert($contestBracketRound->getArrayCopy());
            $filedId = $this->tableGateway->getLastInsertValue();
            return $filedId;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function update(ContestBracketRound $contestBracketRound)
    {
        try {
            $this->updated($contestBracketRound);
            if (!$this->isValid($contestBracketRound)) {
                return false;
            }
            $this->tableGateway->update($contestBracketRound->getArrayCopy());
            return true;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function fetchRecord($contestBracketRoundId)
    {
        $rowset = $this->tableGateway->select(array('id' => $contestBracketRoundId));
        $contestRow = $rowset->current();
        return $contestRow;
    }   
    
    public function fetchAll()
    {
        $select = new \Zend\Db\Sql\Select ;
        $select->from(array('c' => 'contest_bracket_round'))
                ->columns(array('*'));
         
        $statement = $this->getSql()->prepareStatementForSqlObject($select); 
        $resultSet = $statement->execute(); 
        
        return $resultSet;
    }
    
    public function fetchRecordOnContestId($contestId)
    {
        $rowset = $this->tableGateway->select(array('contest_id' => $contestId));
        $contestRow = $rowset->current();
        return $contestRow;
    }  
}
