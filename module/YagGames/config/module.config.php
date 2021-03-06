<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'controllers' => array(
        'invokables' => array(
            'YagGames\Controller\Contest' => 'YagGames\Controller\ContestController',
            'YagGames\Controller\FanFavorite' => 'YagGames\Controller\FanFavoriteController',
            'YagGames\Controller\PhotoContest' => 'YagGames\Controller\PhotoContestController',
            'YagGames\Controller\Brackets' => 'YagGames\Controller\BracketsController',
            'YagGames\Controller\Media' => 'YagGames\Controller\MediaController',
            
            // Console
            'YagGames\Console\SendContestResultsEmail' => 'YagGames\Console\SendContestResultsEmailController',
            'YagGames\Console\SendSuccessSubmissionEmail' => 'YagGames\Console\SendSuccessSubmissionEmailController',
            'YagGames\Console\SendVotingStartEmail' => 'YagGames\Console\SendVotingStartEmailController',
            'YagGames\Console\StartVoting' => 'YagGames\Console\StartVotingController',            
            'YagGames\Console\BracketsRoundCheck' => 'YagGames\Console\BracketsRoundCheckController',
            'YagGames\Console\ModifyRankings' => 'YagGames\Console\ModifyRankingsController'
        ),
        'factories' => array(
            'YagGames\Console\AnnounceWinners' => function(Zend\Mvc\Controller\ControllerManager $cm) {
                $membershipService = $cm->getserviceLocator()->get('membershipService');
                $couponService = $cm->getserviceLocator()->get('couponService');                
                $mediaImage = $cm->getServiceLocator()->get('mediaImage');
                $ordinal = $cm->getServiceLocator()->get('ordinal');
                $kCrypt = $cm->getServiceLocator()->get('kcryptService');
                $announceWinners = new YagGames\Console\AnnounceWinnersController($membershipService, $couponService, $mediaImage, $ordinal, $kCrypt);
                return $announceWinners;
            }
        )
    ),
    
    'controller_plugins' => array(
        'factories' => array(
            'sessionPlugin' => function(Zend\Mvc\Controller\PluginManager $pluginManager) {
                $sessionService = $pluginManager->getServiceLocator()->get('sessionService');
                $sessionPlugin = new YagGames\Controller\Plugin\SessionPlugin();
                $sessionPlugin->setSessionService($sessionService);
                return $sessionPlugin;
            },
        ),
    ),
    
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/[:action][/page/:page][/size/:size][/id/:id][/mid/:mid]',
                    'constraints' => array(
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'page'     => '[0-9]+',
                        'size'     => '[0-9]+',
                        'id'     => '[0-9]+',
                        'mid'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'YagGames\Controller',
                        'controller'    => 'Contest',
                        'action'        => 'new-contest',
                    ),
                ),
            ),
            'media' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/media/[:action][/page/:page][/size/:size][/mid/:mid]',
                    'constraints' => array(
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'page'     => '[0-9]+',
                        'size'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'YagGames\Controller',
                        'controller'    => 'Media',
                        'action'        => 'index',
                    ),
                ),
            ),
            'photo-contest' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/photo-contest[/:action][/:id][/page/:page][/size/:size][/mid/:mid]',
                    'constraints' => array(
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                        'page'     => '[0-9]+',
                        'size'     => '[0-9]+',
                        'mid'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'YagGames\Controller',
                        'controller'    => 'PhotoContest',
                        'action'        => 'rankings',
                    ),
                ),
            ),
            'brackets' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/brackets[/:action][/:id][/page/:page][/size/:size][/mid/:mid]',
                    'constraints' => array(
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                        'page'     => '[0-9]+',
                        'size'     => '[0-9]+',
                        'mid'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'YagGames\Controller',
                        'controller'    => 'Brackets',
                        'action'        => 'rankings',
                    ),
                ),
            ),
            'fan-favorite' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/fan-favorite/[:action]',
                    'constraints' => array(
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'YagGames\Controller',
                        'controller'    => 'FanFavorite',
                        'action'        => 'contest',
                    ),
                ),
            ),
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'factories' => array(
            'translator' => 'Zend\Mvc\Service\TranslatorServiceFactory',
        ),
    ),
    
    'view_manager' => array(
        'display_not_found_reason' => false,
        'display_exceptions'       => false,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'yag-games/index/index' => __DIR__ . '/../view/yag-games/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
            'paginator-slide' => __DIR__ . '/../view/layout/pagination.phtml',
        ),
        'template_path_stack' => array(
            'yaggames' => __DIR__ . '/../view',
        ),
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
                // Console
                'StartVoting' => array(
                  'options' => array(
                      'route'    => 'StartVoting',
                      'defaults' => array(
                          'controller' => 'YagGames\Console\StartVoting',
                          'action'     => 'index'
                      )
                  )
                ),
                'AnnounceWinners' => array(
                  'options' => array(
                      'route'    => 'AnnounceWinners',
                      'defaults' => array(
                          'controller' => 'YagGames\Console\AnnounceWinners',
                          'action'     => 'index'
                      )
                  )
                ),
                'SendContestResultsEmail' => array(
                  'options' => array(
                      'route'    => 'SendContestResultsEmail <contestId>',
                      'defaults' => array(
                          'controller' => 'YagGames\Console\SendContestResultsEmail',
                          'action'     => 'index'
                      )
                  )
                ),
                'SendSuccessSubmissionEmail' => array(
                  'options' => array(
                      'route'    => 'SendSuccessSubmissionEmail <contestMediaId>',
                      'defaults' => array(
                          'controller' => 'YagGames\Console\SendSuccessSubmissionEmail',
                          'action'     => 'index'
                      )
                  )
                ),
                'SendVotingStartEmail' => array(
                  'options' => array(
                      'route'    => 'SendVotingStartEmail [<contestId>]',
                      'defaults' => array(
                          'controller' => 'YagGames\Console\SendVotingStartEmail',
                          'action'     => 'index'
                      )
                  )
                ),
                'BracketsRoundCheck' => array(
                  'options' => array(
                      'route'    => 'BracketsRoundCheck',
                      'defaults' => array(
                          'controller' => 'YagGames\Console\BracketsRoundCheck',
                          'action'     => 'index'
                      )
                  )
                ),
                'ModifyRankings' => array(
                  'options' => array(
                      'route'    => 'ModifyRankings',
                      'defaults' => array(
                          'controller' => 'YagGames\Console\ModifyRankings',
                          'action'     => 'index'
                      )
                  )
                ),
            ),
        ),
    ),
    
    'view_helpers' => array(
      'invokables' => array(
         'config' => 'YagGames\View\Helper\ConfigHelper',
         'ordinal' => 'YagGames\View\Helper\OrdinalHelper',
      ),
      'factories' => array(
        'session' => function (Zend\View\HelperPluginManager $helperPluginManager) {
            $sessionService = $helperPluginManager->getServiceLocator()->get('sessionService');
            $sessionHelper = new YagGames\View\Helper\SessionHelper();
            $sessionHelper->setSessionService($sessionService);
            return $sessionHelper;
        },
        'guestSession' => function (Zend\View\HelperPluginManager $helperPluginManager) {
            $guestSessionService = $helperPluginManager->getServiceLocator()->get('guestSessionService');
            $guestSessionHelper = new YagGames\View\Helper\GuestSessionHelper();
            $guestSessionHelper->setSessionService($guestSessionService);
            return $guestSessionHelper;
        },        
        'mediaImage' => function (Zend\View\HelperPluginManager $helperPluginManager) {
            $kcryptService = $helperPluginManager->getServiceLocator()->get('kcryptService');
            $sessionHelper = new YagGames\View\Helper\MediaImageHelper();
            $sessionHelper->setKCryptService($kcryptService);
            return $sessionHelper;
        },
        'KCrypt' => function (Zend\View\HelperPluginManager $helperPluginManager) {
            $kcryptService = $helperPluginManager->getServiceLocator()->get('kcryptService');
            $sessionHelper = new YagGames\View\Helper\KCryptHelper();
            $sessionHelper->setKCryptService($kcryptService);
            return $sessionHelper;
        }      
      ),
    ),
);
