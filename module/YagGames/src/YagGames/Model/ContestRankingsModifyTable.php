<?php

namespace YagGames\Model;

use Zend\Db\Sql\Predicate\Expression;

class ContestRankingsModifyTable extends BaseTable
{

    public function insert(ContestRankingsModify $contestRankingsModify)
    {
        try {
            if (!$this->isValid($contestRankingsModify)) {
                return false;
            }
            $this->tableGateway->insert($contestRankingsModify->getArrayCopy());
            $filedId = $this->tableGateway->getLastInsertValue();
            return $filedId;
        } catch (Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function update(ContestRankingsModify $contestRankingsModify)
    {
        try {
            if (!$this->isValid($contestRankingsModify)) {
                return false;
            }
            $this->tableGateway->update($contestRankingsModify->getArrayCopy());
            return true;
        } catch (Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

    public function fetchRecordByMediaId($contestMediaId)
    {
        $rowset = $this->tableGateway->select(array('contest_media_id' => $contestMediaId));
        $contestRow = $rowset->current();

        return $contestRow;
    }

    public function fetchRecordsByContest($contestId)
    {
        try {
            $sql = $this->getSql();
            $query = $sql->select()
                    ->from(array('crm' => 'contest_rankings_modify'))
                    ->join(array('cm' => 'contest_media'), 'cm.id = crm.contest_media_id', array())
                    ->where(array(
                'cm.contest_id' => $contestId,
                'crm.status' => 1
            ));

            $rows = $sql->prepareStatementForSqlObject($query)->execute();

            $records = array();
            foreach ($rows as $row) {
                $records[$row['contest_media_id']] = $row;
            }

            return $records;
        } catch (Exception $ex) {
            $this->logException($ex);
            return false;
        }
    }

    public function fetchActiveRecords()
    {
        try {
            $sql = $this->getSql();
            $query = $sql->select()
                            ->from(array('crm' => 'contest_rankings_modify'))
                            ->join(array('cm' => 'contest_media'), 'cm.id = crm.contest_media_id', array())
                            ->join(array('c' => 'contest'), 'c.id = cm.contest_id', array('contest_id' => 'id'))
                            ->where(array(
                                'crm.status' => 1,
                                'c.voting_started' => 1,
                                'c.winners_announced' => 0,
                                'c.type_id' => 1,
                                new Expression('DATE(c.winners_announce_date) > CURDATE()')
                            ))->order('crm.id ASC');

            $rows = $sql->prepareStatementForSqlObject($query)->execute();

            $records = array();
            foreach ($rows as $row) {
                $records[$row['contest_media_id']] = $row;
            }

            return $records;
        } catch (Exception $ex) {
            $this->logException($ex);
            return false;
        }
    }

    public function inActivateByMediaId($contestMediaId)
    {
        try {
            $this->tableGateway->update(array('status' => 0), array('contest_media_id' => $contestMediaId));
            return true;
        } catch (Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
    }

}
