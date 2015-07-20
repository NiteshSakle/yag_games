<?php

namespace YagGames\Model;

class ContestMediaTable extends BaseTable
{

  public function insert(ContestMedia $contestMedia)
  {
    try {

      $this->created($contestMedia);
      if (!$this->isValid($contestMedia)) {
        return false;
      }
      $this->tableGateway->insert($contestMedia->getArrayCopy());
      $filedId = $this->tableGateway->getLastInsertValue();
      return $filedId;
    } catch (\Exception $e) {
      $this->logger->err($e->getMessage());
      return false;
    }
  }

  public function update(ContestMedia $contestMedia)
  {
    try {
      $this->updated($contestMedia);
      if (!$this->isValid($contestMedia)) {
        return false;
      }
      $this->tableGateway->update($contestMedia->getArrayCopy());
      return true;
    } catch (\Exception $e) {
      $this->logger->err($e->getMessage());
      return false;
    }
  }

  public function fetchRecord($contestMediaId)
  {
    $rowset = $this->tableGateway->select(array('id' => $contestMediaId));
    $contestRow = $rowset->current();
    return $contestRow;
  }

  public function fetchContestMedia($contestId, $mediaId)
  {
    $rowset = $this->tableGateway->select(array('contest_id' => $contestId, 'media_id' => $mediaId));
    $contestRow = $rowset->current();
    return $contestRow;
  }

  public function fetchAll()
  {
    $select = new \Zend\Db\Sql\Select;
    $select->from(array('c' => 'contest_media'))
            ->columns(array('*'));

    $statement = $this->getSql()->prepareStatementForSqlObject($select);
    $resultSet = $statement->execute();

    return $resultSet;
  }

  public function getContestMedia($contestId, $userId = null, $keyword = null, $page = 1, $offset = 20)
  {
    try {
      $sql = $this->getSql();
      $columns = array('*', 'votes' => new Expression('SUM(cmr.id)'));
      $query = $sql->select()
              ->from(array('cm' => 'contest_media'))
              ->join(array('m' => 'ps4_media'), 'm.media_id = cm.meida_id')
              ->join(array('u' => 'ps4_members'), 'm.owner = u.mem_id')
              ->join(array('cmr' => 'contest_media_rating'), 'cm.id = cmr.contest_meida_id')
              ->quantifier(new Expression('SQL_CALC_FOUND_ROWS'))
              ->columns($columns)
              ->where('cm.contest_id = ?', $contestId)
              ->limit($offset)
              ->offset(($page - 1) * $offset)
              ->order("votes DESC")
              ->group('cm.media_id');

      if (!empty($keyword)) {
        $query->where->like('u.f_name', "%" . $keyword . "%");
      }

      if (!empty($userId)) {
        $userId = (int) $userId;
        $columns['is_liked'] = new Expression('COUNT(CASE WHEN cmr.member_id = ' . $userId . ' THEN 1 ELSE 0 END)');
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
    } catch (\Exception $e) {
      return array(
          "total" => 0,
          "medias" => array()
      );
    }
  }

  public function getContestMediaCount($contestId, $userId = null)
  {
    try {
      $sql = $this->getSql();
      $columns = array('*', 'count' => new Expression('COUNT(cmr.id)'));
      $query = $sql->select()
              ->from(array('cm' => 'contest_media'))
              ->columns($columns)
              ->where(array('cm.contest_id' => $contestId))
              ->group('cm.contest_id');

      if (!empty($userId)) {
        $userId = (int) $userId;
        $columns['has_uploaded'] = new Expression('COUNT(CASE WHEN cmr.member_id = ' . $userId . ' THEN 1 ELSE 0 END)');
        $query->columns($columns);
      }

      $rows = $sql->prepareStatementForSqlObject($query)->execute();
      if (isset($rows[0])) {
        return $rows[0];
      } else {
        return false;
      }
    } catch (\Exception $e) {
      return false;
    }
  }

}
