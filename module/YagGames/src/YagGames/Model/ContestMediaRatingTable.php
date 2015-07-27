<?php

namespace YagGames\Model;

use Exception;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;

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
      $columns = array('rating' => new Expression('AVG(cmr.rating)'));
      $query = $sql->select()
              ->from(array('cmr' => 'contest_media_rating'))
              ->join(array('cm' => 'contest_media'), 'cm.id = cmr.contest_media_id', array('contest_media_id' => 'id'))
              ->columns($columns)
              ->where(array(
                  'cm.contest_id' => $contestId
              ))
              ->order('rating DESC')
              ->limit('10')
              ->group('cm.media_id');

      $rows = $sql->prepareStatementForSqlObject($query)->execute();
      $row = $rows->current();
      if ($row) {
        return $row;
      } else {
        return false;
      }
    } catch (Exception $e) {
      $this->logException($e);
      return false;
    }
  }

}
