<?php

namespace YagGames\Model;

class ContestMediaTable extends BaseTable
{

    public function insert(ContestMedia $contestMedia)
    {
        try {
            
            $this->created($contestMedia);
            if (!$this->isValid($contestMedia)) {
                return false;
            }
            $this->tableGateway->insert($contestMedia->getArrayCopy());
            $filedId = $this->tableGateway->getLastInsertValue();
            return $filedId;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function update(ContestMedia $contestMedia)
    {
        try {
            $this->updated($contestMedia);
            if (!$this->isValid($contestMedia)) {
                return false;
            }
            $this->tableGateway->update($contestMedia->getArrayCopy());
            return true;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function fetchRecord($contestMediaId)
    {
        $rowset = $this->tableGateway->select(array('id' => $contestMediaId));
        $contestRow = $rowset->current();
        return $contestRow;
    }   
    
    public function fetchAll()
    {
        $select = new \Zend\Db\Sql\Select ;
        $select->from(array('c' => 'contest_media'))
                ->columns(array('*'));
         
        $statement = $this->getSql()->prepareStatementForSqlObject($select); 
        $resultSet = $statement->execute(); 
        
        return $resultSet;
    }
}
