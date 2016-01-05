<?php

namespace YagGames\Model;

use SebastianBergmann\RecursionContext\Exception;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;

class SettingsTable
{

  public function __construct($adapter, $logger)
  {
    $this->adapter = $adapter;
    $this->logger = $logger;
  }

  public function fetchAll()
  { 
    $select = new Select;
    $select->from(array('m' => 'ps4_settings'))
            ->join(array('m2' => 'ps4_settings2'), 'm2.settings_id = m.settings_id', array('*'))
            ->columns(array('*'))
            ->where('m.settings_id = 1');

    try {
      $sql = new Sql($this->adapter);
      $statement = $sql->prepareStatementForSqlObject($select);
      $resultSet = $statement->execute();
      return $resultSet->current();
    } catch (Exception $e) {
      $this->logger->ERR($e->getMessage() . "\n" . $e->getTraceAsString());
    }
    return false;
  }

}
