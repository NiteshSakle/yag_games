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
    
    public function getTopRatedMediaForNextRound($contestId, $round) 
    {
        try {                 
            
            $sqlStr = "SELECT x.*,
                       CASE
                         WHEN x.cmediaid1_votes > x.cmediaid2_votes THEN x.contest_media_id1
                         WHEN x.cmediaid2_votes > x.cmediaid1_votes THEN x.contest_media_id2
                         WHEN ( x.contest_media_id1 != 0 && x.contest_media_id2 != 0 ) THEN
                           CASE
                             WHEN contest_media_id1 < contest_media_id2 THEN x.contest_media_id1
                             ELSE x.contest_media_id2
                           END
                         WHEN x.contest_media_id2 = 0 THEN x.contest_media_id1
                         ELSE x.contest_media_id2
                       END AS next_round_media_id
                      FROM (SELECT cbmc.*,
                               (SELECT COUNT(id) FROM contest_media_rating cmr1 WHERE  cmr1.contest_media_id = cbmc.contest_media_id1 AND round = :round) AS cmediaid1_votes,
                               (SELECT COUNT(id) FROM contest_media_rating cmr1 WHERE  cmr1.contest_media_id = cbmc.contest_media_id2 AND round = :round) AS cmediaid2_votes FROM contest_bracket_media_combo cbmc WHERE cbmc.contest_id = :contestId AND cbmc.round = :round) 
                      x ORDER  BY x.combo_id ASC";
            
            $sqlStmt = $this->tableGateway->adapter->createStatement($sqlStr, array(
                           'contestId' => $contestId,
                           'round' => $round
                        ));
             
            $resultset = $sqlStmt->execute();
            
            $records = array();
            
            foreach ($resultset as $row) {
                $records[] = $row;
            }
            
            return $records;
        } catch (Exception $e) {
            $this->logException($e);
            return false;
        }
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
