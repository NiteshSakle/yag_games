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
            'YagGames\Controller\Media' => 'YagGames\Controller\MediaController',
        ),
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
                    'route'    => '/[:action]',
                    'constraints' => array(
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
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
                    'route'    => '/media/[:action][/page/:page][/size/:size]',
                    'constraints' => array(
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
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
                    'route'    => '/photo-contest[/:action][/:id][/page/:page][/size/:size]',
                    'constraints' => array(
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                        'page'     => '[0-9]+',
                        'size'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'YagGames\Controller',
                        'controller'    => 'PhotoContest',
                        'action'        => 'view',
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
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
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
            ),
        ),
    ),
    
    'view_helpers' => array(
      'invokables' => array(
         'config' => 'YagGames\View\Helper\ConfigHelper',
      ),
      'factories' => array(
        'session' => function (Zend\View\HelperPluginManager $helperPluginManager) {
            $sessionService = $helperPluginManager->getServiceLocator()->get('sessionService');
            $sessionHelper = new YagGames\View\Helper\SessionHelper();
            $sessionHelper->setSessionService($sessionService);
            return $sessionHelper;
        },
        'mediaImage' => function (Zend\View\HelperPluginManager $helperPluginManager) {
            $kcryptService = $helperPluginManager->getServiceLocator()->get('kcryptService');
            $sessionHelper = new YagGames\View\Helper\MediaImageHelper();
            $sessionHelper->setKCryptService($kcryptService);
            return $sessionHelper;
        },
      ),
    ),
);
