<?php

namespace YagGames\Model;

class ContestWinnerTable extends BaseTable
{

    public function insert(ContestWinner $contestWinner)
    {
        try {
            
            $this->created($contestWinner);
            if (!$this->isValid($contestWinner)) {
                return false;
            }
            $this->tableGateway->insert($contestWinner->getArrayCopy());
            $filedId = $this->tableGateway->getLastInsertValue();
            return $filedId;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function update(ContestWinner $contestWinner)
    {
        try {
            $this->updated($contestWinner);
            if (!$this->isValid($contestWinner)) {
                return false;
            }
            $this->tableGateway->update($contestWinner->getArrayCopy());
            return true;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function fetchRecord($contestWinnerId)
    {
        $rowset = $this->tableGateway->select(array('id' => $contestWinnerId));
        $contestRow = $rowset->current();
        return $contestRow;
    }   
    
    public function fetchAll()
    {
        $select = new \Zend\Db\Sql\Select ;
        $select->from(array('c' => 'contest_winner'))
                ->columns(array('*'));
         
        $statement = $this->getSql()->prepareStatementForSqlObject($select); 
        $resultSet = $statement->execute(); 
        
        return $resultSet;
    }
}
