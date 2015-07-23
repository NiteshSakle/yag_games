<?php

namespace YagGames\Model;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;

class ContestTable extends BaseTable
{

  public function insert(Contest $contest)
  {
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

  public function update(Contest $contest)
  {
    try {
      $this->updated($contest);
      if (!$this->isValid($contest)) {
        return false;
      }
      $this->tableGateway->update($contest->getArrayCopy());
      return true;
    } catch (\Exception $e) {
      $this->logger->err($e->getMessage());
      return false;
    }
  }

  public function fetchRecord($contestId)
  {
    $rowset = $this->tableGateway->select(array('id' => $contestId));
    $contestRow = $rowset->current();
    return $contestRow;
  }

  public function fetchAllByType($type = '', $page = 1, $user = null)
  {

    $select = new Select;
    $select->from(array('c' => 'contest'))
            ->columns(array('*', 'my_type' => new Expression('IF(entry_end_date >= NOW(), "new", IF(winners_announce_date >=NOW(), "active", "past"))')))
            ->join(array('cm' => 'contest_media'), 'c.id = cm.contest_id', array('total_entries' => new Expression('COUNT(cm.id)')), 'left')
            ->group('c.id');

    if ($type == 'new') {
      $select->where('entry_end_date >= NOW()');
//      $select->join(array('m' => 'ps4_media'), new Expression('cm.media_id = m.media_id AND m.owner=?', $user), array('entered' => new Expression('IF(m.media_id, 1, 0 )')), 'left')
//              ->where('entry_end_date >= NOW()');
    } elseif ($type == 'active') {

      $select->where('entry_end_date <= NOW() AND winners_announce_date >= NOW()');
    } elseif ($type == 'past') {

      $select->where('winners_announce_date <= NOW()');
    } elseif ($type == 'my') {

      $select->join(array('cm1' => 'contest_media'), 'c.id = cm1.contest_id', 'media_id')
              ->join(array('m' => 'ps4_media'), 'cm1.media_id = m.media_id', 'media_id')
              ->where(array('m.owner' => $user));
    } elseif ($type == 'exclusive') {

      $select->join(array('cm1' => 'contest_media'), 'c.id = cm1.contest_id', 'media_id')
              ->join(array('m' => 'ps4_media'), 'cm1.media_id = m.media_id', 'media_id')
              ->where(array('m.owner' => $user));
    }

    $select->order('c.entry_end_date');
    $select->limit(10);

    $statement = $this->getSql()->prepareStatementForSqlObject($select);
    $resultSet = $statement->execute();

    $contest = array();
    foreach ($resultSet as $row) {
      $contest[] = $row;
    }

    return $contest;
  }

}
