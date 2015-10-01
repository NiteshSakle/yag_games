<?php

namespace YagGames\Model;

use Exception;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;

class ContestMediaTable extends BaseTable {

    public function insert(ContestMedia $contestMedia) {
        try {

            $this->created($contestMedia);
            if (!$this->isValid($contestMedia)) {
                return false;
            }
            $this->tableGateway->insert($contestMedia->getArrayCopy());
            $filedId = $this->tableGateway->getLastInsertValue();
            return $filedId;
        } catch (Exception $e) {
            $this->logException($e);
            return false;
        }
    }

    public function update(ContestMedia $contestMedia) {
        try {
            $this->updated($contestMedia);
            if (!$this->isValid($contestMedia)) {
                return false;
            }
            $this->tableGateway->update($contestMedia->getArrayCopy());
            return true;
        } catch (Exception $e) {
            $this->logException($e);
            return false;
        }
    }

    public function delete($params) {
        try {
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('contest_id', $params['contest_id'])
                    ->equalTo('media_id', $params['media_id']);
            return $this->tableGateway->delete($where);
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function fetchRecord($contestMediaId) {
        $rowset = $this->tableGateway->select(array('id' => $contestMediaId));
        $contestRow = $rowset->current();
        return $contestRow;
    }

    public function fetchContestMedia($contestId, $mediaId) {
        $rowset = $this->tableGateway->select(array('contest_id' => $contestId, 'media_id' => $mediaId));
        $contestRow = $rowset->current();
        return $contestRow;
    }

    public function fetchAll() {
        $select = new Select;
        $select->from(array('c' => 'contest_media'))
                ->columns(array('*'));

        $statement = $this->getSql()->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();

        return $resultSet;
    }

    public function fetchAllByContest($contestId) {
        $select = new \Zend\Db\Sql\Select;
        $select->from(array('c' => 'contest_media'))
                ->columns(array('*'))
                ->join(array('m' => 'ps4_media'), 'c.media_id = m.media_id')
                ->where(array('contest_id' => $contestId));

        $statement = $this->getSql()->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();

        $photos = array();
        foreach ($resultSet as $row) {
            $photos[] = $row;
        }

        return $photos;
    }
    
    public function getContestMediaDetails($contestMediaId) {
        try {

            $sql = $this->getSql();
            $query = $sql->select()
                    ->from(array('c' => 'contest'))
                    ->join(array('cm' => 'contest_media'), 'cm.contest_id = c.id', array('*'))
                    ->join(array('m' => 'ps4_media'), 'm.media_id = cm.media_id', array('*'))
                    ->join(array('u' => 'ps4_members'), 'm.owner = u.mem_id', array('username', 'f_name', 'l_name', 'email'))
                    ->where(array('cm.id' => $contestMediaId))
                    ->group('cm.media_id');
            
            $rows = $sql->prepareStatementForSqlObject($query)->execute();
            $row = $rows->current();

            return $row;
        } catch (Exception $e) {
            $this->logException($e);
            return false;
        }
    }

    public function getContestMedia($contestId, $userId = null, $keyword = null, $page = 1, $offset = 20, $sort = 'rank') {
        try {
            $sort = ($sort == 'rank') ? 'rank DESC' : 'cm.id ASC';

            $sql = $this->getSql();
            $columns = array('*', 'rank' => new Expression('AVG(cmr.rating)'));
            $query = $sql->select()
                    ->from(array('cm' => 'contest_media'))
                    ->join(array('m' => 'ps4_media'), 'm.media_id = cm.media_id')
                    ->join(array('u' => 'ps4_members'), 'm.owner = u.mem_id', array('username', 'f_name', 'email'))
                    ->join(array('cmr' => 'contest_media_rating'), 'cm.id = cmr.contest_media_id', array(), 'left')
                    ->quantifier(new Expression('SQL_CALC_FOUND_ROWS'))
                    ->columns($columns)
                    ->where(array('cm.contest_id' => $contestId))
                    ->limit($offset)
                    ->offset(($page - 1) * $offset)
                    ->order($sort)
                    ->group('cm.media_id');

            if (!empty($keyword)) {
                $query->where->like('u.f_name', "%" . $keyword . "%");
            }

            if (!empty($userId)) {
                $userId = (int) $userId;
                $columns['is_liked'] = new Expression('COUNT(CASE WHEN cmr.member_id = ' . $userId . ' THEN 1 ELSE NULL END)');
                $query->columns($columns);
            }

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
            $this->logException($e);
            return array(
                "total" => 0,
                "medias" => array()
            );
        }
    }

    public function getContestMediaCount($contestId, $userId = null) {
        try {
            $sql = $this->getSql();
            $columns = array('*', 'count' => new Expression('COUNT(cm.id)'));
            $query = $sql->select()
                    ->from(array('cm' => 'contest_media'))
                    ->columns($columns)
                    ->where(array('cm.contest_id' => $contestId))
                    ->group('cm.contest_id');

            if (!empty($userId)) {
                $userId = (int) $userId;
                $columns['has_uploaded'] = new Expression('COUNT(CASE WHEN m.owner = ' . $userId . ' THEN 1 ELSE NULL END)');

                $query->join(array('m' => 'ps4_media'), 'm.media_id = cm.media_id', array(), 'left');
                $query->join(array('u' => 'ps4_members'), 'm.owner = u.mem_id', array(), 'left');
                $query->columns($columns);
            }

            $rows = $sql->prepareStatementForSqlObject($query)->execute();
            $row = $rows->current();
            if ($row) {
                return $row;
            } else {
                return array('count' => 0, 'has_uploaded' => 0);
            }
        } catch (Exception $e) {
            $this->logException($e);
            return array('count' => 0, 'has_uploaded' => 0);
        }
    }

    public function getNextContestMedia($contestId, $mediaId, $clientIP, $config, $userId = null, $ratedMedia = array()) {
        try {
            $limit = 1;
            $sql = $this->getSql();
            $columns = array('contest_name' => 'name', 'max_no_of_photos', 'votes' => new Expression('COUNT(cmr.id)'));
            $query = $sql->select()
                    ->from(array('c' => 'contest'))
                    ->join(array('cm' => 'contest_media'), 'cm.contest_id = c.id', array('*'))
                    ->join(array('m' => 'ps4_media'), 'm.media_id = cm.media_id', array('*'))
                    ->join(array('u' => 'ps4_members'), 'm.owner = u.mem_id', array('username', 'f_name'))
                    ->join(array('cmr' => 'contest_media_rating'), 'cm.id = cmr.contest_media_id', array(), 'left')
                    ->columns($columns)
                    ->where(array('cm.contest_id' => $contestId))
                    ->order("votes")
                    ->group('cm.media_id');

            if (!empty($mediaId)) {
                $query->where(array('cm.media_id' => $mediaId));
            }
            
            if (is_array($config) && !in_array($clientIP, $config['white_listed_ips'])) {           
               
               $subQry1 = $sql->select()
                          ->from(array('cmr3' => 'contest_media_rating'))
                          ->columns(array('contest_media_id'))
                          ->where(new Expression('DATE(cmr3.created_at) = CURDATE()'))
                          ->where(array('cmr3.ip_address' => $clientIP));                          
               
               $query->where(
                        new \Zend\Db\Sql\Predicate\PredicateSet(
                        array(
                    new \Zend\Db\Sql\Predicate\NotIn('cm.id', $subQry1)
                        )
                        )
                );
            }
            
            if (!empty($userId)) {
                //exclude already rated media of today
                $subQry = $sql->select()
                        ->from(array('cmr2' => 'contest_media_rating'))
                        ->columns(array('contest_media_id'))
                        ->where(array(
                    'cmr2.member_id' => $userId,
                    new Expression('DATE(cmr2.created_at) = CURDATE()')
                ));

                $query->where(
                        new \Zend\Db\Sql\Predicate\PredicateSet(
                        array(
                    new \Zend\Db\Sql\Predicate\NotIn('cm.id', $subQry)
                        )
                        )
                );
            } else if (count($ratedMedia)) {
                //add not in condition to eliminate rated media
                $query->where(new \Zend\Db\Sql\Predicate\NotIn('cm.media_id', $ratedMedia));
            }

            $rows = $sql->prepareStatementForSqlObject($query)->execute();            
            $contestMedia = array();
            foreach ($rows as $row) {
                $contestMedia[] = $row;
            }                   
            // Get a Random Image from det of all Contest Media's
            $row = isset($contestMedia[rand(0, count($contestMedia)-1)])?$contestMedia[rand(0, count($contestMedia)-1)]:null;
            if ($row) {
                $count = $this->getContestMediaCount($contestId, $userId);
                $row['count'] = $count['count'];
                return $row;
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->logException($e);
            return false;
        }
    }
    
    public function getUserContestMediaCount($contestId, $userId = null) {
        try {
            $sql = $this->getSql();
            $query = $sql->select()
                    ->from(array('cm' => 'contest_media'))
                    ->columns(array('*'))
                    ->where(array('cm.contest_id' => $contestId));

            $userId = (int) $userId;            
            $query->join(array('m' => 'ps4_media'), 'm.media_id = cm.media_id', array(), 'left')
                    ->where(array('m.owner' => $userId));            

            $rows = $sql->prepareStatementForSqlObject($query)->execute();
            $row = $rows->current();
            
            return $row;            
        } catch (Exception $e) {
            $this->logException($e);
            return array('count' => 0, 'has_uploaded' => 0);
        }
    }

    public function fetchAllUserMediaDetailsByContest($contestId) {
        $select = new \Zend\Db\Sql\Select;
        $select->from(array('c' => 'contest_media'))
                ->columns(array('*'))
                ->join(array('m' => 'ps4_media'), 'c.media_id = m.media_id')
                ->join(array('mem' => 'ps4_members'), 'mem.mem_id = m.owner')
                ->where(array('contest_id' => $contestId));

        $statement = $this->getSql()->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();

        $photos = array();
        foreach ($resultSet as $row) {
            $photos[] = $row;
        }

        return $photos;
    }
    
    public function fetchBracketContestMedia($contestId, $userId = null) {
        
        $resultSet = $this->getContestMedia($contestId, $userId, NULL, 1, 64);

        $photos = array();
        foreach ($resultSet['medias'] as $row) {
            $photos[$row['id']] = $row;
        }

        return array(
                "total" => $this->getFoundRows(),
                "medias" => $photos
            );
    }
    
    public function getNextBracketCombo($contestId, $userId = null, $contestComboId, $ratedComboMedia = array(), $round, $whiteListedIP, $clientIP) {
        try {
            $limit = 1;
            $sql = $this->getSql();
            $columns = array('contest_name' => 'name', 'votes' => new Expression('COUNT(cbmc.combo_id)'));
            $query = $sql->select()
                    ->from(array('c' => 'contest'))
                    ->columns($columns)
                    ->join(array('cbr' => 'contest_bracket_round'), 'cbr.contest_id = c.id', array('*'))
                    ->join(array('cbmc' => 'contest_bracket_media_combo'), new Expression(' c.id = cbmc.contest_id AND cbr.current_round = cbmc.round') , array('*'))
                    ->join(array('cmr' => 'contest_media_rating'), new Expression('cbmc.combo_id = cmr.bracket_combo_id AND cbmc.round = cmr.round'), array(), 'left')
                    ->where(array('c.id' => $contestId))
                    ->limit($limit)
                    ->group('cbmc.combo_id')
                    ->order('cbmc.combo_id');
            
            if (!empty($contestComboId)) {
                $query->where(array('cbmc.combo_id' => $contestComboId)); 
            }
            
            if (is_array($whiteListedIP) && !in_array($clientIP, $whiteListedIP)) {
               $subQry1 = $sql->select()
                          ->from(array('cmr3' => 'contest_media_rating'))
                          ->columns(array('bracket_combo_id'))
                          ->join(array('cm3' => 'contest_media'), new Expression('cm3.id = cmr3.contest_media_id'), array())
                          ->where(array(
                                 'cmr3.ip_address' => $clientIP,
                                 'cmr3.round' => $round,
                                 'cm3.contest_id' => $contestId,
                             ));
               
               $query->where(new \Zend\Db\Sql\Predicate\PredicateSet(
                       array(new \Zend\Db\Sql\Predicate\NotIn('cbmc.combo_id', $subQry1))
                        ));
            }
            
            if (!empty($userId)) {
                //exclude already rated media of this round
                $subQry = $sql->select()
                        ->from(array('cmr2' => 'contest_media_rating'))
                        ->columns(array('bracket_combo_id'))
                        ->join(array('cm2' => 'contest_media'), new Expression('cm2.id = cmr2.contest_media_id'), array())
                        ->where(array(
                    'cmr2.member_id' => $userId,
                    'cmr2.round' => $round,
                    'cm2.contest_id' => $contestId,
                ));

                $query->where(
                        new \Zend\Db\Sql\Predicate\PredicateSet(
                        array(
                    new \Zend\Db\Sql\Predicate\NotIn('cbmc.combo_id', $subQry)
                        )
                        )
                );
            } else if (count($ratedComboMedia)) {
                //add not in condition to eliminate rated media
                $query->where(new \Zend\Db\Sql\Predicate\NotIn('cbmc.combo_id', $ratedComboMedia));
            }
            
            $rows = $sql->prepareStatementForSqlObject($query)->execute();
            
            return $rows->current();
        } catch (Exception $e) {
            $this->logException($e);
            return false;
        }
    }
    
    public function getNextBracketMedia($contestId, $userId = null, $contestComboId, $ratedMedia = array(), $round, $whiteListedIP = array(), $clientIP)
    {
        try {
            $comboDetails = $this->getNextBracketCombo($contestId, $userId, $contestComboId, $ratedMedia, $round, $whiteListedIP, $clientIP);
            $media = array();
            if($comboDetails) {
                $limit = 2;
                $sql = $this->getSql();            
                $query = $sql->select()                    
                        ->from(array('cm' => 'contest_media'))
                        ->columns(array('*'))
                        ->join(array('m' => 'ps4_media'), 'm.media_id = cm.media_id', array('*'))
                        ->join(array('u' => 'ps4_members'), 'm.owner = u.mem_id', array('username', 'f_name'))
                        ->where(array('cm.contest_id' => $contestId))
                        ->limit($limit)
                        ->group('cm.media_id');
                
                $query->where
                        ->NEST
                        ->equalTo('cm.id', $comboDetails['contest_media_id1'])
                        ->OR
                        ->equalTo('cm.id', $comboDetails['contest_media_id2'])
                        ->UNNEST;  
                 
                $rows = $sql->prepareStatementForSqlObject($query)->execute();
                
                foreach ($rows as $row) {
                    $media[$row['id']] = $row;
                }
            } 
            return array(
                "contestDetails" => $comboDetails,
                "medias" => $media
            );         
        } catch (Exception $e) {
            $this->logException($e);
            return false;
        }
    }
}
