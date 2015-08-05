<?php

namespace YagGames\Model;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;

class ContestTable extends BaseTable {
    
    //ADMIN: adding a new contest
    public function insert(Contest $contest) {
        try {

            $this->created($contest);
            if (!$this->isValid($contest)) {
                return false;
            }
            $this->tableGateway->insert($contest->getArrayCopy());
            $filedId = $this->tableGateway->getLastInsertValue();
            return $filedId;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    //ADMIN: editing a existing contest
    public function update(Contest $contest) {
        try {
            $this->updated($contest);
            if (!$this->isValid($contest)) {
                return false;
            }
            $updated_data = array();
            if ($contest->name) {
                $updated_data['name'] = $contest->name;
            }
            if ($contest->description) {
                $updated_data['description'] = $contest->description;
            }
            if ($contest->thumbnail) {
                $updated_data['thumbnail'] = $contest->thumbnail;
            }
            if ($contest->entry_end_date) {
                $updated_data['entry_end_date'] = $contest->entry_end_date;
            }
            if ($contest->winners_announce_date) {
                $updated_data['winners_announce_date'] = $contest->winners_announce_date;
            }
            if ($contest->voting_start_date) {
                $updated_data['voting_start_date'] = $contest->voting_start_date;
            }
            if ($contest->max_no_of_photos) {
                $updated_data['max_no_of_photos'] = $contest->max_no_of_photos;
            }
            if ($contest->voting_started) {
                $updated_data['voting_started'] = $contest->voting_started;
            }
            if ($contest->is_exclusive) {
                $updated_data['is_exclusive'] = $contest->is_exclusive;
            }
            if ($contest->type_id) {
                $updated_data['type_id'] = $contest->type_id;
            }
            if ($contest->updated_at) {
                $updated_data['updated_at'] = $contest->updated_at;
            }

            $this->tableGateway->update($updated_data, array('id' => $contest->id));
            return true;
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    //ADMIN: removing a existing contest
    public function delete($id) {
        try {
            return $this->tableGateway->delete(array('id' => $id));
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function fetchRecord($contestId) {
        $rowset = $this->tableGateway->select(array('id' => $contestId));
        $contestRow = $rowset->current();
        return $contestRow;
    }

    public function fetchAll() {
        $select = new \Zend\Db\Sql\Select;
        $select->from(array('c' => 'contest'))
                ->join(array('ct' => 'contest_type'), 'ct.id = c.type_id', array('type'))
                ->columns(array('*'))
                ->order('entry_end_date desc');

        $statement = $this->getSql()->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();

        $types = array();
        foreach ($resultSet as $row) {
            $types[] = $row;
        }

        return $types;
    }

    public function fetchAllByType($type = '', $user = null, $page = 1, $offset = 10) {
        try {
            $select = new Select;
            $select->from(array('c' => 'contest'))
                    ->columns(array('*', 'my_type' => new Expression('IF(entry_end_date >= NOW(), "new", IF(winners_announce_date >=NOW(), "active", "past"))')))
                    ->join(array('ct' => 'contest_type'), 'ct.id = c.type_id', array('contest_type' => 'type'))
                    ->join(array('cm' => 'contest_media'), 'c.id = cm.contest_id', array('total_entries' => new Expression('COUNT(cm.id)')), 'left')
                    ;
            
            $subColumns = array('inner_contest_id' => 'contest_id', 'total_ratings_count' => new Expression('COUNT(cm2.id)'));
            $contestMediaCountQry = $this->getSql()->select()
                      ->from(array('cm2' => 'contest_media'))
                      ->columns($subColumns)
                      ->group('cm2.contest_id');

            if ($type == 'new') {
                $select->join(array('cm_sub' => $contestMediaCountQry), 'cm_sub.inner_contest_id = cm.contest_id', array('total_ratings_count'), 'left');
                $select->where('c.entry_end_date >= CURDATE()');
                $select->where->and->notEqualTo('c.is_exclusive', '1');
                $select->where('(total_ratings_count < c.max_no_of_photos OR total_ratings_count IS NULL)');
                
//                $select->where->nest
//                                ->lessThan('total_ratings_count', 'c.max_no_of_photos')
//                                ->or
//                                ->isNull('total_ratings_count')
//                              ->unnest;
                
                // if user log's in, check whether he entered the contest or not
                if ($user) {
                    $select->join(array('m' => 'ps4_media'), new Expression('cm.media_id = m.media_id AND m.owner = ?', $user), array('entered' => new Expression('IF(m.media_id, 1, 0 )'), 'media_id', 'folder_id'), 'left');
                }
            } elseif ($type == 'active') {

              $select->join(array('cm_sub' => $contestMediaCountQry), 'cm_sub.inner_contest_id = cm.contest_id', array('total_ratings_count'), 'left');
              $select->where('(entry_end_date < CURDATE() AND winners_announce_date > CURDATE()) AND c.is_exclusive <> 1 OR total_ratings_count >= c.max_no_of_photos');
//              $select->where->and->notEqualTo('c.is_exclusive', '1');
//              $select->where->or->greaterThanOrEqualTo('total_ratings_count', 'c.max_no_of_photos');
              
            } elseif ($type == 'past') {
                // if user log's in, check whether his media rank
                if ($user) {
                    $select->join(array('m' => 'ps4_media'), new Expression('cm.media_id = m.media_id AND m.owner = ?', $user), array('entered' => new Expression('IF(m.media_id, 1, 0 )')), 'left');
                    $select->join(array('cw' => 'contest_winner'), 'cm.id = cw.contest_media_id', array('rank'), 'left');
                }

                $select->where('winners_announce_date <= NOW() AND c.is_exclusive <> 1');
            } elseif ($type == 'my') {
                // show only user medias
                $select->join(array('cm1' => 'contest_media'), 'c.id = cm1.contest_id', 'media_id')
                        ->join(array('m' => 'ps4_media'), 'cm1.media_id = m.media_id', 'media_id')
                        ->join(array('cw' => 'contest_winner'), 'cm1.id = cw.contest_media_id', array('rank'), 'left')
                        ->where(array('m.owner' => $user));
            } elseif ($type == 'exclusive') {
                $select->where(array('c.is_exclusive' => 1));
            }

            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
            $select->order('c.entry_end_date');
            $select->group('c.id');
            $select->limit($offset);
            $select->offset(($page - 1) * $offset);
            
            $statement = $this->getSql()->prepareStatementForSqlObject($select);
            $resultSet = $statement->execute();

            $contests = array();
            foreach ($resultSet as $row) {
                $contests[] = $row;
            }
            
            return array(
                "total" => $this->getFoundRows(),
                "contests" => $contests
            );
        } catch (\Exception $e) {
            $this->logException($e);
            return array(
                "total" => 0,
                "contests" => array()
            );
        }
    }

    public function getContestArtistEmails($contestId) {
        try {
            $sql = $this->getSql();
            $query = $sql->select()
                    ->from(array('c' => 'contest'))
                    ->join(array('cm' => 'contest_media'), 'cm.contest_id = c.id', array())
                    ->join(array('m' => 'ps4_media'), 'm.media_id = cm.media_id', array())
                    ->join(array('u' => 'ps4_members'), 'm.owner = u.mem_id', array('username', 'f_name', 'email'))
                    ->where(array(
                        'c.id' => $contestId,
                    ))
                    ->group('u.mem_id');

            $rows = $sql->prepareStatementForSqlObject($query)->execute();

            $contest = array();
            foreach ($rows as $row) {
                $contest[] = $row['email'];
            }

            return $contest;
        } catch (\Exception $e) {
            $this->logException($e);
            return false;
        }
    }

    public function getVotingReadyContests() {
        try {
            
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('c.voting_started', '0')
                    ->lessThanOrEqualTo('c.voting_start_date', new Expression('CURDATE()'));
            $sql = $this->getSql();
            $query = $sql->select()
                    ->from(array('c' => 'contest'))
                    ->where($where)
                    ->group('c.id');
            
            $rows = $sql->prepareStatementForSqlObject($query)->execute();
            $contest = array();
            foreach ($rows as $row) {
                $contest[] = $row;
            }
            
            return $contest;
        } catch (\Exception $e) {
            $this->logException($e);
            return false;
        }
    }

    public function getWinnersToBeAnouncedContests() {
        try {
            $sql = $this->getSql();
            $query = $sql->select()
                    ->from(array('c' => 'contest'))
                    ->where(array(
                        'c.voting_started' => 1,
                        new Expression('DATE(c.winners_announce_date) = CURDATE()')
                    ))
                    ->group('c.id');

            $rows = $sql->prepareStatementForSqlObject($query)->execute();

            $contest = array();
            foreach ($rows as $row) {
                $contest[] = $row;
            }

            return $contest;
        } catch (\Exception $e) {
            $this->logException($e);
            return false;
        }
    }

    //ADMIN: get all contests page wise(page_size = 10)
    public function getAllContests($page = 1) {
        try {
            $offset = 0;
            $limit = 10;
            if ($page > 1) {
                $offset = $page * 10 - 10;
            }

            $select = new \Zend\Db\Sql\Select;
            $select->from(array('c' => 'contest'))
                    ->join(array('ct' => 'contest_type'), 'ct.id = c.type_id', array('type'))
                    ->columns(array('*'))
                    ->group('c.id')
                    ->order('entry_end_date desc')
                    ->limit($limit)
                    ->offset($offset);

            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));

            //echo $select->getSqlString();exit;
            
            $statement = $this->getSql()->prepareStatementForSqlObject($select);
            $resultSet = $statement->execute();

            $contests = array();
            foreach ($resultSet as $row) {
                $contests[] = $row;
            }
            return array(
                "total" => $this->getFoundRows(),
                "contests" => $contests
            );
        } catch (\Exception $e) {
            $this->logException($e);
            return array(
                "total" => 0,
                "contests" => array()
            );
        }
    }

}
