<?php

namespace YagGames\Model;

use Exception;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;

class MediaViewTable extends BaseTable
{

    public function fetchRecord($contestMediaId)
    {
        $rowset = $this->tableGateway->select(array('id' => $contestMediaId));
        $contestRow = $rowset->current();
        return $contestRow;
    }   
    
    public function fetchAll($userId)
    {
        $select = new Select ;
        $select->from(array('m' => 'rating_view'))
                ->columns(array('*'))
                ->where('m.owner = ?', $userId);
         
        $statement = $this->getSql()->prepareStatementForSqlObject($select); 
        $resultSet = $statement->execute(); 
        
        return $resultSet;
    }
    
    public function getMyMedia($userId, $page = 1, $offset = 25) {
        try {
            $sql = $this->getSql();
            $columns = array('*');
            $query = $sql->select()
                ->from(array('rv' => 'rating_view'))
                ->quantifier(new Expression('SQL_CALC_FOUND_ROWS'))
                ->columns($columns)
                ->where(array('rv.owner' => $userId, 'active' => '1'))
                ->limit($offset)
                ->offset(($page - 1) * $offset)
                ->order("rv.rating DESC")
                ->group('rv.media_id');

            $rows = $sql->prepareStatementForSqlObject($query)->execute();

            $media = array();
            foreach ($rows as $row) {
                $media[] = $row;
            }            
            
            return array(
                "total" => $this->getFoundRows(),
                "medias" => $media
            );
        } catch (Exception $e) {
            return array(
                "total" => 0,
                "medias" => array()
            );
        }
    }
    
    public function getMyMediaRank($userId, $mediaId)
    {
        try {
            $sqlStr = "SELECT z.rank
                        FROM
                          ( SELECT rv.media_id, @rownum := @rownum + 1 AS rank
                           FROM rating_view rv,
                             (SELECT @rownum := 0) r
                           WHERE rv.owner = :userId
                             AND rv.active = 1
                           GROUP BY rv.media_id
                           ORDER BY rv.rating DESC) as z
                        WHERE media_id = :mediaId";
            $sqlStmt = $this->tableGateway->adapter->createStatement($sqlStr, array(
                'userId' => $userId,
                'mediaId' => $mediaId
            ));

            $resultset = $sqlStmt->execute();         

            $rank = 0;
            foreach ($resultset as $row) {
                $rank = $row['rank'];
            }
            
            return $rank;
        } catch (Exception $e) {
            return array(
                "total" => 0,
                "medias" => array()
            );
        }
    }

}
