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
            
            $updateData = array();
            
            if($contestBracketRound->contest_id) {                
                $updateData['contest_id'] = $contestBracketRound->contest_id;
            }
            
            if ($contestBracketRound->round1) {
                $updateData['round1'] = $contestBracketRound->round1;
            }
            
            if ($contestBracketRound->round2) {
                $updateData['round2'] = $contestBracketRound->round2;
            }
            
            if ($contestBracketRound->round3) {
                $updateData['round3'] = $contestBracketRound->round3;
            }
            
            if ($contestBracketRound->round4) {
                $updateData['round4'] = $contestBracketRound->round4;
            }
            
            if ($contestBracketRound->round5) {
                $updateData['round5'] = $contestBracketRound->round5;
            }
            
            if ($contestBracketRound->round6) {
                $updateData['round6'] = $contestBracketRound->round6;
            }
            
            if ($contestBracketRound->current_round) {
                $updateData['current_round'] = $contestBracketRound->current_round;
            }
            
            if ($contestBracketRound->created_at) {
                $updateData['created_at'] = $contestBracketRound->created_at;
            }
            
            if ($contestBracketRound->updated_at) {
                $updateData['updated_at'] = $contestBracketRound->updated_at;
            }
            
            $this->tableGateway->update($updateData, array('id' => $contestBracketRound->id));
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
    
    public function fetchAllActiveContests() 
    {
        $select = new \Zend\Db\Sql\Select ;
        $select->from(array('cbr' => 'contest_bracket_round'))
                ->columns(array('*'))
                ->join(array('c' => 'contest'), 'c.id = cbr.contest_id', array('name'))
                ->where(array('c.voting_started' => 1));
         
        $statement = $this->getSql()->prepareStatementForSqlObject($select); 
        $resultSet = $statement->execute(); 
        
        return $resultSet;
    }
}
