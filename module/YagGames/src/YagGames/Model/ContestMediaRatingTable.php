<?php

namespace YagGames\Model;

use Exception;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression as Expr;

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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        $select = new Select;
        $select->from(array('c' => 'contest_media_rating'))
                ->columns(array('*'));

        $statement = $this->getSql()->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();

        return $resultSet;
    }

    public function totalRatedForThisContestToday($contestId, $userId)
    {
        try {
            $sql = $this->getSql();
            $columns = array('count' => new Expression('COUNT(cmr.id)'));
            $query = $sql->select()
                    ->from(array('cmr' => 'contest_media_rating'))
                    ->join(array('cm' => 'contest_media'), 'cm.id = cmr.contest_media_id')
                    ->columns($columns)
                    ->where(array(
                'cm.contest_id' => $contestId,
                'cmr.member_id' => $userId,
                new Expression('DATE(cmr.created_at) = CURDATE()')
            ));

            $rows = $sql->prepareStatementForSqlObject($query)->execute();
            $row = $rows->current();
            if ($row) {
                return $row['count'];
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->logException($e);
            return false;
        }
    }

    public function hasAlreadyVotedForThisContestMediaToday($contestMediaId, $userId)
    {
        try {
            $sql = $this->getSql();
            $columns = array('count' => new Expression('COUNT(cmr.id)'));
            $query = $sql->select()
                    ->from(array('cmr' => 'contest_media_rating'))
                    ->columns($columns)
                    ->where(array(
                'cmr.contest_media_id' => $contestMediaId,
                'cmr.member_id' => $userId,
                new Expression('DATE(cmr.created_at) = CURDATE()')
            ));

            $rows = $sql->prepareStatementForSqlObject($query)->execute();
            $row = $rows->current();
            if ($row) {
                return $row['count'];
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->logException($e);
            return false;
        }
    }

    public function getTop10RatedMedia($contestId)
    {
        try {
            $sql = $this->getSql();

            $query = $sql->select()
                    ->from(array('cmr' => 'contest_media_rating'))
                    ->join(array('cm' => 'contest_media'), 'cm.id = cmr.contest_media_id', array('contest_media_id' => 'id', 'media_id'))
                    ->join(array('m' => 'ps4_media'), 'm.media_id = cm.media_id', array('owner'))
                    ->columns(array('rating' => new Expression('AVG(cmr.rating)')))
                    ->where(array(
                        'cm.contest_id' => $contestId
                    ))
                    ->group('cm.media_id')
                    ->order('rating desc, cm.created_at asc')
                    ->limit(10);

            $rows = $sql->prepareStatementForSqlObject($query)->execute();
            $winners = array();
            foreach ($rows as $row) {
                $winners[] = $row;
            }

            return $winners;
        } catch (Exception $e) {
            $this->logException($e);
            return false;
        }
    }

    public function isMediaRatedFromIpToday($contestId, $mediaId, $ipAddress)
    {
        try {
            $sql = $this->getSql();

            $query = $sql->select()
                    ->from(array('cmr' => 'contest_media_rating'))
                    ->join(array('cm' => 'contest_media'), 'cm.id = cmr.contest_media_id')
                    ->where(array('cm.contest_id' => $contestId, 'cm.media_id' => $mediaId, 'cmr.ip_address' => $ipAddress))
                    ->where(new Expression('DATE(cmr.created_at) = CURDATE()'));

            $rows = $sql->prepareStatementForSqlObject($query)->execute();

            if ($rows->count() > 0) {
                return TRUE;
            }

            return FALSE;
        } catch (Exception $ex) {
            $this->logException($ex);
            return false;
        }
    }

    public function hasAlreadyVotedForThisBracketContest($round, $comboId, $userId, $contestId)
    {
        try {
            $sql = $this->getSql();
            $columns = array('count' => new Expression('COUNT(cmr.id)'));
            $query = $sql->select()
                    ->from(array('cmr' => 'contest_media_rating'))
                    ->join(array('cm' => 'contest_media'), 'cm.id = cmr.contest_media_id')
                    ->columns($columns)
                    ->where(array(
                'cmr.bracket_combo_id' => $comboId,
                'cmr.member_id' => $userId,
                'cmr.round' => $round,
                'cm.contest_id' => $contestId,
            ));

            $rows = $sql->prepareStatementForSqlObject($query)->execute();
            $row = $rows->current();
            if ($row) {
                return $row['count'];
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->logException($e);
            return false;
        }
    }

    public function totalRatedForThisBracketRound($contestId, $userId, $round)
    {
        try {
            $sql = $this->getSql();
            $columns = array('count' => new Expression('COUNT(cmr.id)'));
            $query = $sql->select()
                    ->from(array('cmr' => 'contest_media_rating'))
                    ->join(array('cm' => 'contest_media'), 'cm.id = cmr.contest_media_id')
                    ->columns($columns)
                    ->where(array(
                'cm.contest_id' => $contestId,
                'cmr.member_id' => $userId,
                'cmr.round' => $round
            ));

            $rows = $sql->prepareStatementForSqlObject($query)->execute();
            $row = $rows->current();
            if ($row) {
                return $row['count'];
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->logException($e);
            return false;
        }
    }

    public function getPhotoContestMediaRank($contestId, $contestMediaRatingId)
    {
        try {
            $this->executeRawQuery('SET @rank = 0');
            $sql = $this->getSql();
            $columns = array('*', 'avg_rating' => new Expression('AVG(cmr.rating)'));
            $innerQry2 = $sql->select()
                    ->from(array('cmr' => 'contest_media_rating'))
                    ->join(array('cm' => 'contest_media'), 'cm.id = cmr.contest_media_id', array())
                    ->join(array('c' => 'contest'), 'c.id = cm.contest_id', array())
                    ->columns($columns)
                    ->where(array(
                        'c.id' => $contestId,
                        'c.type_id' => 1
                    ))
                    ->order('avg_rating DESC')
                    ->group('cm.media_id');

            $innerQry1 = $sql->select()
                    ->from(array('b' => $innerQry2))
                    ->columns(array('*', 'rank' => new Expression('@rank := @rank + 1')));            

            $outerQry = $sql->select()
                    ->from(array('a' => $innerQry1))
                    ->having(array('contest_media_id' => $contestMediaRatingId));

            $rows = $sql->prepareStatementForSqlObject($outerQry)->execute();
            $row = $rows->current();

            return $row;
        } catch (Exception $ex) {
            $this->logException($ex);
            return false;
        }
    }

    public function reduceRank($contestMediaId, $limit = 5)
    {
        try {                
            $sql = $this->getSql();
            $innerQry2 = $sql->select()
                        ->from(array('cmr' => 'contest_media_rating'))
                        ->join(array('cm' => 'contest_media'), 'cm.id = cmr.contest_media_id', array())
                        ->join(array('c' => 'contest'), 'c.id = cm.contest_id', array())
                        ->columns(array('id'))
                        ->where(array(
                            'cmr.contest_media_id' => $contestMediaId,
                            'cmr.rating <> 2',
                            'c.voting_started' => 1,
                            'c.winners_announced' => 0,
                            'c.announce_winners_under_process' => 0,
                            'c.type_id' => 1
                        ))
                        ->order('rating DESC')
                        ->limit($limit);
            
            $innerQry1 = $sql->select()
                         ->from(array('a' => $innerQry2))
                         ->columns(array('id'));
                    
            $outerQry = $sql->update()
                        ->table('contest_media_rating')
                        ->set(array('rating' => 2))
                        ->where(
                        new \Zend\Db\Sql\Predicate\PredicateSet(
                        array(
                            new \Zend\Db\Sql\Predicate\In('id', $innerQry1)
                        )
                        ));                        
            
            $affectedRows = $sql->prepareStatementForSqlObject($outerQry)->execute()->getAffectedRows(); 
            
            return $affectedRows;
        } catch (Exception $e) {
            echo $e->getMessage();
            $this->logger->err($e->getMessage());
            return false;
        }
    }
            
    public function getVotingDetails($contestMediaId, $page = 1, $searchText)
    {
        try { 
            $offset = 0;
            $limit = 50;
            if ($page > 1) {
                $offset = $page * 50 - 50;
            }

            //Total Votes Per Rating
            $select = new Select;
            $select->from(array('cmr' => 'contest_media_rating'))
                    ->join(array('m' => 'ps4_members'), 'm.mem_id = cmr.member_id', array('f_name','l_name'), 'left') 
                ->columns(array(
                    'rate' => 'rating',
                    'total_rate_count' => new Expression('COUNT(cmr.id)')
                ))
                ->where(array(
                        'cmr.contest_media_id' => $contestMediaId,
                       ))
                ->group('cmr.rating');
            
            if ($searchText != "") {
                $select->where
                        ->nest()
                            ->like(new Expression("CONCAT(m.f_name, ' ', m.l_name)"), "%" . $searchText . "%")
                            -> OR 
                            ->like('cmr.ip_address', "%" . $searchText . "%")
                        ->unnest();
            }

            $statement = $this->getSql()->prepareStatementForSqlObject($select);
            $countPerRating = $statement->execute();

            //Voting Details
            $select = new Select;
            $select->from(array('cmr' => 'contest_media_rating'))
                    ->join(array('m' => 'ps4_members'), 'm.mem_id = cmr.member_id', array('f_name','l_name'), 'left')                    
                    ->columns(array('*'))
                    ->where(array(
                            'cmr.contest_media_id' => $contestMediaId,
                           ))
                    ->limit($limit)
                    ->offset($offset)
                    ->order('cmr.created_at DESC');
            
            if ($searchText != "") {
                $select->where
                        ->nest()
                            ->like(new Expression("CONCAT(m.f_name, ' ', m.l_name)"), "%" . $searchText . "%")
                            -> OR 
                            ->like('cmr.ip_address', "%" . $searchText . "%")
                        ->unnest();            }
            
            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
            $statement = $this->getSql()->prepareStatementForSqlObject($select);
            $resultSet = $statement->execute();
            

            return array(
                "total" => $this->getFoundRows(),
                "resultSet" => $resultSet,
                'countPerRating' => $countPerRating                
            );
            
        } catch (Exception $ex) {
            $this->logException($ex);
            return false;
        }                
    }

}
