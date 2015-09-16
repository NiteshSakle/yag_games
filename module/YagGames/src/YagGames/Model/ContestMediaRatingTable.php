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
  
  public function hasAlreadyVotedForThisBracketContest($round, $comboId , $userId, $contestId){
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
}
