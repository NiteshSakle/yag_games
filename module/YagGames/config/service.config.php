<?php

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use YagGames\Model\Contest;
use YagGames\Model\ContestTable;
use YagGames\Model\ContestBracketMediaCombo;
use YagGames\Model\ContestBracketMediaComboTable;
use YagGames\Model\ContestBracketRound;
use YagGames\Model\ContestBracketRoundTable;
use YagGames\Model\ContestMedia;
use YagGames\Model\ContestMediaTable;
use YagGames\Model\ContestMediaRating;
use YagGames\Model\ContestMediaRatingTable;
use YagGames\Model\ContestType;
use YagGames\Model\ContestTypeTable;
use YagGames\Model\ContestWinner;
use YagGames\Model\ContestWinnerTable;

return array(
    
    'factories' => array(
        'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
                
        'YagGames\Logger' => function () {
            $log = new Zend\Log\Logger();
            $writer = new Zend\Log\Writer\Stream(dirname(__FILE__) . '/../../../data/log/log');
            $log->addWriter($writer);
            return $log;
        },
        
        'YagGames\Model\ContestTable' => function ($sm) {
            $tableGateway = $sm->get('ContestTableGateway');
            $table = new ContestTable($tableGateway, $sm->get('YagGames\Logger'));
            return $table;
        },
        'ContestTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Contest());
            return new TableGateway('contest', $dbAdapter, null, $resultSetPrototype);
        },
        'YagGames\Model\ContestBracketMediaComboTable' => function ($sm) {
            $tableGateway = $sm->get('ContestBracketMediaComboTableGateway');
            $table = new ContestBracketMediaComboTable($tableGateway, $sm->get('YagGames\Logger'));
            return $table;
        },
        'ContestBracketMediaComboTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new ContestBracketMediaCombo());
            return new TableGateway('contest_bracket_media_combo', $dbAdapter, null, $resultSetPrototype);
        },
        'YagGames\Model\ContestBracketRoundTable' => function ($sm) {
            $tableGateway = $sm->get('ContestBracketRoundTableGateway');
            $table = new ContestBracketRoundTable($tableGateway, $sm->get('YagGames\Logger'));
            return $table;
        },
        'ContestBracketRoundTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new ContestBracketRound());
            return new TableGateway('contest_bracket_round', $dbAdapter, null, $resultSetPrototype);
        },
        'YagGames\Model\ContestMediaRatingTable' => function ($sm) {
            $tableGateway = $sm->get('ContestMediaRatingTableGateway');
            $table = new ContestMediaRatingTable($tableGateway, $sm->get('YagGames\Logger'));
            return $table;
        },
        'ContestMediaRatingTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new ContestMediaRating());
            return new TableGateway('contest_media_rating', $dbAdapter, null, $resultSetPrototype);
        },
        'YagGames\Model\ContestMediaTable' => function ($sm) {
            $tableGateway = $sm->get('ContestMediaTableGateway');
            $table = new ContestMediaTable($tableGateway, $sm->get('YagGames\Logger'));
            return $table;
        },
        'ContestMediaTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new ContestMedia());
            return new TableGateway('contest_media', $dbAdapter, null, $resultSetPrototype);
        },
        'YagGames\Model\ContestTypeTable' => function ($sm) {
            $tableGateway = $sm->get('ContestTypeTableGateway');
            $table = new ContestTypeTable($tableGateway, $sm->get('YagGames\Logger'));
            return $table;
        },
        'ContestTypeTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new ContestType());
            return new TableGateway('contest_type', $dbAdapter, null, $resultSetPrototype);
        },
        'YagGames\Model\ContestWinnerTable' => function ($sm) {
            $tableGateway = $sm->get('ContestWinnerTableGateway');
            $table = new ContestWinnerTable($tableGateway, $sm->get('YagGames\Logger'));
            return $table;
        },
        'ContestWinnerTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new ContestWinner());
            return new TableGateway('contest_winner', $dbAdapter, null, $resultSetPrototype);
        },
    ),
);
