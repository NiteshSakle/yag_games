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
    
    public function fetchAllWinnersOfContest($contestId, $limit = 0)
    {
        $select = new \Zend\Db\Sql\Select ;
        $select->from(array('cw' => 'contest_winner'))
                ->columns(array('*'))
                ->join(array('cm' => 'contest_media'), 'cm.id = cw.contest_media_id')
                ->join(array('m' => 'ps4_media'), 'm.media_id = cm.media_id', array('*'))
                ->join(array('u' => 'ps4_members'), 'm.owner = u.mem_id', array('username', 'f_name', 'l_name', 'email'))
                ->where(array('cm.contest_id' => $contestId))
                ->order('cw.rank');
        
        if($limit)
         $select->limit ($limit);
        
        $statement = $this->getSql()->prepareStatementForSqlObject($select);                 
        $resultSet = $statement->execute(); 
        $media = array();
            foreach ($resultSet as $row) {
                $media[] = $row;
            }   
        return $media;
    }
}
