<?php

use YagGames\Model\Contest;
use YagGames\Model\ContestBracketMediaCombo;
use YagGames\Model\ContestBracketMediaComboTable;
use YagGames\Model\ContestBracketRound;
use YagGames\Model\ContestBracketRoundTable;
use YagGames\Model\ContestMedia;
use YagGames\Model\ContestMediaRating;
use YagGames\Model\ContestMediaRatingTable;
use YagGames\Model\ContestMediaTable;
use YagGames\Model\ContestTable;
use YagGames\Model\ContestType;
use YagGames\Model\ContestTypeTable;
use YagGames\Model\ContestWinner;
use YagGames\Model\ContestWinnerTable;
use YagGames\Model\MediaTable;
use YagGames\Model\MediaViewTable;
use YagGames\Model\MonthlyAward;
use YagGames\Model\MonthlyAwardTable;
use YagGames\Model\Members;
use YagGames\Model\MembersTable;
use YagGames\Model\Promotions;
use YagGames\Model\PromotionsTable;
use YagGames\Model\SettingsTable;
use YagGames\Service\FanFavoriteService;
use YagGames\Service\KCryptService;
use YagGames\Service\PhotoContestService;
use YagGames\Service\BracketService;
use YagGames\Service\SessionService;
use YagGames\Utils\Process;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container;
use YagGames\Service\FbScrapService;
use YagGames\Service\ClientIPService;
use YagGames\Service\CouponService;
use YagGames\Service\MediaImageService;
use YagGames\Service\MembershipService;
use YagGames\View\Helper\OrdinalHelper;

return array(
    
    'factories' => array(
        'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
                
        'YagGames\Logger' => function () {
            $log = new Logger();
            $writer = new Stream(dirname(__FILE__) . '/../../../data/log/log');
            $log->addWriter($writer);
            return $log;
        },
                
        'YagGames\Utils\Process' => function ($sm) {
            $process = new Process($sm->get('Request'));
            return $process;
        },
                
        'photoContestService' => function(ServiceLocatorInterface $serviceLocator) {
            $photoContestService = new PhotoContestService($serviceLocator);
            return $photoContestService;
        },
        
        'bracketService' => function(ServiceLocatorInterface $serviceLocator) {
            $bracketService = new BracketService($serviceLocator);
            return $bracketService;
        },
                
        'fanFavoriteService' => function(ServiceLocatorInterface $serviceLocator) {
            $fanFavoriteService = new FanFavoriteService($serviceLocator);
            return $fanFavoriteService;
        },
                
        'sessionService' => function(ServiceLocatorInterface $serviceLocator) {
            $sessionContainer = new Container('member');
            $sessionService = new SessionService();
            $sessionService->setSessionContainer($sessionContainer);
            return $sessionService;
        },
                
        'kcryptService' => function(ServiceLocatorInterface $serviceLocator) {
            $kcryptService = new KCryptService();
            $kcryptService->setSettingsTable($serviceLocator->get('YagGames\Model\SettingsTable'));
            $kcryptService->setConfig($serviceLocator->get('Config'));
            return $kcryptService;
        },
                
        'fbScrapService' => function(ServiceLocatorInterface $serviceLocator) {
            $fbScrapService = new FbScrapService($serviceLocator);
            return $fbScrapService;
        }, 
        
        'clientIPService' => function(ServiceLocatorInterface $serviceLocator) {
            $clientIPService = new ClientIPService($serviceLocator);
            return $clientIPService;
        },        
        'couponService' => function(ServiceLocatorInterface $serviceLocator) {
            $couponService = new CouponService($serviceLocator);
            return $couponService;
        },        
        'mediaImage' => function (ServiceLocatorInterface $serviceLocator) {
            $config = $serviceLocator->get('config');
            $kcryptService = $serviceLocator->get('kcryptService');                    
            $mediaImageService = new MediaImageService();            
            $mediaImageService->setKCryptService($kcryptService, $config);
            return $mediaImageService;
        },
        'membershipService' => function (ServiceLocatorInterface $serviceLocator) {
            $membershipService = new MembershipService($serviceLocator);
            return $membershipService;
        },
        'ordinal' => function(ServiceLocatorInterface $serviceLocator) {
            $ordinalService = new OrdinalHelper();
            return $ordinalService;
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
        'YagGames\Model\MediaTable' => function ($sm) {
            $tableGateway = $sm->get('MediaTableGateway');
            $table = new MediaTable($tableGateway, $sm->get('YagGames\Logger'));
            return $table;
        },
        'MediaTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            return new TableGateway('ps4_media', $dbAdapter);
        },
        'YagGames\Model\MediaViewTable' => function ($sm) {
            $tableGateway = $sm->get('MediaViewTableGateway');
            $table = new MediaViewTable($tableGateway, $sm->get('YagGames\Logger'));
            return $table;
        },
        'MediaViewTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            return new TableGateway('rating_view', $dbAdapter);
        },
                
        'YagGames\Model\SettingsTable' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $table = new SettingsTable($dbAdapter, $sm->get('YagGames\Logger'));
            return $table;
        },
        'YagGames\Model\MonthlyAwardTable' => function ($sm) {
            $tableGateway = $sm->get('MonthlyAwardTableGateway');
            $table = new MonthlyAwardTable($tableGateway, $sm->get('YagGames\Logger'));
            return $table;
        },
        'MonthlyAwardTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new MonthlyAward());
            return new TableGateway('ps4_monthly_award', $dbAdapter, null, $resultSetPrototype);
        },
        'YagGames\Model\MembersTable' => function ($sm) {
            $tableGateway = $sm->get('MembersTableGateway');
            $table = new MembersTable($tableGateway, $sm->get('YagGames\Logger'));
            return $table;
        },
        'MembersTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Members());
            return new TableGateway('ps4_members', $dbAdapter, null, $resultSetPrototype);
        },        
        'YagGames\Model\PromotionsTable' => function ($sm) {
            $tableGateway = $sm->get('PromotionsTableGateway');
            $table = new PromotionsTable($tableGateway, $sm->get('YagGames\Logger'));
            return $table;
        },
        'PromotionsTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Promotions());
            return new TableGateway('ps4_promotions', $dbAdapter, null, $resultSetPrototype);
        }    
    ),
);
