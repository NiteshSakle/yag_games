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
            'YagGames\Controller\Index' => 'YagGames\Controller\IndexController'
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
                        'controller'    => 'Index',
                        'action'        => 'index',
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
    'translator' => array(
        'locale' => 'en_US',
        'translation_file_patterns' => array(
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ),
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
            'yag-games/index/index' => __DIR__ . '/../view/yaggames/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            'yaggames' => __DIR__ . '/../view',
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
      ),
    ),
);
