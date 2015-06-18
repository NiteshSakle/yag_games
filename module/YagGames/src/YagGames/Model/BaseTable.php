<?php

namespace YagGames\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Log\Logger;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;

class BaseTable
{

    protected $tableGateway;
    protected $logger;
    protected $sql;
    protected $filterMessages;

    public function __construct(TableGateway $tableGateway, Logger $logger)
    {
        $this->tableGateway = $tableGateway;
        $this->logger = $logger;
    }

    
    public function getFilterMessages()
    {
        return $this->filterMessages;
    }

    public function isValid($object)
    {
        $filter = $object->getInputFilter();

        if ($filter) {
            $this->filterMessages = '';
            $filter->setData($object->getArrayCopy());
            if ($filter->isValid()) {
                return true;
            } else {
                $this->filterMessages = $filter->getMessages();
                return false;
            }
        }

        return true;
    }

    protected function getSql()
    {
        try {
            $dbAdapter = $this->tableGateway->adapter;
            $sql = new Sql($dbAdapter);
            return $sql;
        } catch (Exception $e) {
            $this->logger->ERR($e->getMessage() . "\n" . $e->getTraceAsString());
        }
        return false;
    }

    protected function created($var)
    {
        $var->created_at = new Expression('NOW()');
        $var->updated_at = new Expression('NOW()');
        return true;
    }

    protected function updated($var)
    {
        $var->updated_at = new Expression('NOW()');
        return true;
    }

    protected function logException(\Exception $e)
    {
        if ($e instanceof \Zend\Db\Adapter\ExceptionInterface) {
            throw new DatabaseException();
        }
        $this->logger->crit("Database Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    }

    protected function getArrayFromResultSet($resultSet)
    {
        $result = array();
        foreach ($resultSet as $projectRow) {
            $result[] = $projectRow;
        }

        return $result;
    }

    protected function hydrateObject(&$resultSet, $columns, $objectName)
    {
        // IMPROVEMENT: Hydrate to actual objects
        if (!$resultSet) {
            return;
        }

        $resultSet[$objectName] = array();
        foreach ($columns as $key => $value) {

            // maintain actual field name in result set
            $fieldName = $value;
            if (is_string($key)) {
                $fieldName = $key;
            }

            if (!array_key_exists($fieldName, $resultSet)) {
                continue;
            }

            // use value in object which hydrates to the real name
            $resultSet[$objectName][$value] = $resultSet[$fieldName];
            unset($resultSet[$fieldName]);
        }
    }

    public function insertOrUpdate(array $insertData, array $updateData)
    {
        $sqlStringTemplate = 'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s';
        $adapter = $this->tableGateway->adapter; /* Get adapter from tableGateway */
        $driver = $adapter->getDriver();
        $platform = $adapter->getPlatform();

        $tableName = $platform->quoteIdentifier($this->tableGateway->getTable());
        $parameterContainer = new \Zend\Db\Adapter\ParameterContainer();
        $statementContainer = $adapter->createStatement();
        $statementContainer->setParameterContainer($parameterContainer);

        // Preparation insert data
        $insertQuotedValue = [];
        $insertQuotedColumns = [];
        foreach ($insertData as $column => $value) {
            $insertQuotedValue[] = $driver->formatParameterName($column);
            $insertQuotedColumns[] = $platform->quoteIdentifier($column);
            $parameterContainer->offsetSet($column, $value);
        }

        // Preparation update data
        $updateQuotedValue = [];
        foreach ($updateData as $column => $value) {
            $updateQuotedValue[] = $platform->quoteIdentifier($column) . '=' . $driver->formatParameterName('update_' . $column);
            $parameterContainer->offsetSet('update_' . $column, $value);
        }

        // Preparation sql query
        $query = sprintf(
                $sqlStringTemplate, $tableName, implode(',', $insertQuotedColumns), implode(',', array_values($insertQuotedValue)), implode(',', $updateQuotedValue)
        );

        $statementContainer->setSql($query);
        return $statementContainer->execute();
    }

    public function randomNumber($digits = 5)
    {
        return rand(pow(10, $digits - 1), pow(10, $digits) - 1);
    }

}
