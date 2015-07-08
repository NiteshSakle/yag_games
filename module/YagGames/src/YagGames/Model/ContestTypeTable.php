<?php

namespace YagGames\Model;

class ContestTypeTable extends BaseTable
{

    public function insert(ContestType $contestType)
    {
        try {
            
            $this->created($contestType);
            if (!$this->isValid($contestType)) {
                return false;
            }
            $this->tableGateway->insert($contestType->getArrayCopy());
            $filedId = $this->tableGateway->getLastInsertValue();
            return $filedId;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function update(ContestType $contestType)
    {
        try {
            $this->updated($contestType);
            if (!$this->isValid($contestType)) {
                return false;
            }
            $this->tableGateway->update($contestType->getArrayCopy());
            return true;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function fetchRecord($contestTypeId)
    {
        $rowset = $this->tableGateway->select(array('id' => $contestTypeId));
        $contestRow = $rowset->current();
        return $contestRow;
    }   
    
    public function fetchAll()
    {
        $select = new \Zend\Db\Sql\Select ;
        $select->from(array('c' => 'contest_type'))
                ->columns(array('*'));
         
        $statement = $this->getSql()->prepareStatementForSqlObject($select); 
        $resultSet = $statement->execute(); 
        
        return $resultSet;
    }
}
