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

    private function getContestBaseSelect() {
        $contestMediaCountQry = $this->getSql()->select()
                ->from(array('cm2' => 'contest_media'))
                ->columns(array(
                    'contest_media_id' => 'id',
                    'contest_id',
                    'media_id',
                    'total_entries' => new Expression('COUNT(cm2.id)')
                ))
                ->group('cm2.contest_id');

        $select = new Select;
        $select->from(array('c' => 'contest'))
                ->columns(array('*', 'my_type' => new Expression('IF(entry_end_date >= CURDATE(), "new", IF(winners_announce_date > CURDATE(), "active", "past"))')))
                ->join(array('ct' => 'contest_type'), 'ct.id = c.type_id', array('contest_type' => 'type'))
                ->join(array('cm' => $contestMediaCountQry), 'c.id = cm.contest_id', array('total_entries'), 'left')
        ;

        return $select;
    }

    private function getNewContestSelect($select, $user) {
        $select->where('c.entry_end_date >= CURDATE()');
        $select->where->and->notEqualTo('c.is_exclusive', '1');
        $select->where('(total_entries < c.max_no_of_photos OR total_entries IS NULL)');

        // if user log's in, check whether he entered the contest or not
        if ($user) {
            $userMediaQry = $this->getSql()->select()
                    ->from(array('cm_new' => 'contest_media'))
                    ->join(array('m_new' => 'ps4_media'), 'cm_new.media_id = m_new.media_id', array('media_id', 'folder_id'))
                    ->where(array('m_new.owner' => $user))
                    ->columns(array(
                'contest_id'
            ));

            $select->join(array('uc' => $userMediaQry), new Expression('c.id = uc.contest_id'), array('entered' => new Expression('IF(uc.contest_id, 1, 0 )'), 'media_id', 'folder_id'), 'left');
        }

        return $select;
    }

    private function getActiveContestSelect($select) {
        $select->where('(entry_end_date < CURDATE() AND winners_announce_date > CURDATE()) AND c.is_exclusive <> 1');

        return $select;
    }

    private function getPastContestSelect($select, $user) {
        // if user log's in, check whether his media rank
        if ($user) {
            $select->join(array('cw' => 'contest_winner'), 'cm.contest_media_id = cw.contest_media_id', array('rank'), 'left');
        }

        $select->where('winners_announce_date <= CURDATE() AND c.is_exclusive <> 1');

        return $select;
    }

    private function getMyContestSelect($select, $user) {
        // show only user medias
        $select->join(array('cm1' => 'contest_media'), 'c.id = cm1.contest_id', 'media_id')
                ->join(array('m' => 'ps4_media'), 'cm1.media_id = m.media_id', 'media_id')
                ->join(array('cw' => 'contest_winner'), 'cm1.id = cw.contest_media_id', array('rank'), 'left')
                ->where(array('m.owner' => $user));

        // if user log's in, check whether he entered the contest or not
        if ($user) {
            $userMediaQry = $this->getSql()->select()
                    ->from(array('cm_new' => 'contest_media'))
                    ->join(array('m_new' => 'ps4_media'), 'cm_new.media_id = m_new.media_id', array('media_id', 'folder_id'))
                    ->where(array('m_new.owner' => $user))
                    ->columns(array(
                'contest_id'
            ));

            $select->join(array('uc' => $userMediaQry), new Expression('c.id = uc.contest_id'), array('entered' => new Expression('IF(uc.contest_id, 1, 0 )'), 'media_id', 'folder_id'), 'left');
        }

        return $select;
    }

    private function getExclusiveContestSelect($select, $user) {
        $select->where(array('c.is_exclusive' => 1));
        // if user log's in, check whether he entered the contest or not
        if ($user) {
            $userMediaQry = $this->getSql()->select()
                    ->from(array('cm_new' => 'contest_media'))
                    ->join(array('m_new' => 'ps4_media'), 'cm_new.media_id = m_new.media_id', array('media_id', 'folder_id'))
                    ->where(array('m_new.owner' => $user))
                    ->columns(array(
                'contest_id'
            ));

            $select->join(array('uc' => $userMediaQry), new Expression('c.id = uc.contest_id'), array('entered' => new Expression('IF(uc.contest_id, 1, 0 )'), 'media_id', 'folder_id'), 'left');
        }

        return $select;
    }

    public function fetchAllByType($type = '', $user = null, $page = 1, $offset = 10) {
        try {

            $select = $this->getContestBaseSelect();

            if ($type == 'new') {
                $select = $this->getNewContestSelect($select, $user);
            } elseif ($type == 'active') {
                $select = $this->getActiveContestSelect($select);
            } elseif ($type == 'past') {
                $select = $this->getPastContestSelect($select, $user);
            } elseif ($type == 'my') {
                $select = $this->getMyContestSelect($select, $user);
            } elseif ($type == 'exclusive') {
                $select = $this->getExclusiveContestSelect($select, $user);
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

    public function getContestArtistData($contestId) {
        try {
            $sql = $this->getSql();
            $query = $sql->select()
                    ->from(array('c' => 'contest'))
                    ->join(array('cm' => 'contest_media'), 'cm.contest_id = c.id', array())
                    ->join(array('m' => 'ps4_media'), 'm.media_id = cm.media_id', array())
                    ->join(array('u' => 'ps4_members'), 'm.owner = u.mem_id', array('username', 'email', 'f_name', 'l_name'))
                    ->where(array(
                        'c.id' => $contestId,
                    ))
                    ->group('u.mem_id');

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
                        new \Zend\Db\Sql\Predicate\Expression('DATE(c.winners_announce_date) = CURDATE()')
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
