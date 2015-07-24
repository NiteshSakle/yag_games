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
            'YagAdmin\Controller\Index' => 'YagAdmin\Controller\IndexController'
        ),
    ),
    
    'controller_plugins' => array(
        'factories' => array(
            'adminSessionPlugin' => function(Zend\Mvc\Controller\PluginManager $pluginManager) {
                $sessionService = $pluginManager->getServiceLocator()->get('adminSessionService');
                $sessionPlugin = new YagGames\Controller\Plugin\SessionPlugin();
                $sessionPlugin->setSessionService($sessionService);
                return $sessionPlugin;
            },
        ),
    ),
    
    'router' => array(
        'routes' => array(
            'admin' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/manager[/:action]',
                    'constraints' => array(
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'YagAdmin\Controller',
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ),
                ),
            ),
        ),
    ),
    
    'view_manager' => array(
        'template_map' => array(
            'yag-admin/layout/layout'   => __DIR__ . '/../view/yag-admin/layout/admin-layout.phtml',
            'yag-admin/index/index'     => __DIR__ . '/../view/yag-admin/index/index.phtml',
        ),
        'template_path_stack' => array(
            'yag-admin' => __DIR__ . '/../view',
        ),
    ),
                    
    'view_helpers' => array(
      'factories' => array(
        'adminSession' => function (Zend\View\HelperPluginManager $helperPluginManager) {
            $sessionService = $helperPluginManager->getServiceLocator()->get('adminSessionService');
            $sessionHelper = new YagGames\View\Helper\SessionHelper();
            $sessionHelper->setSessionService($sessionService);
            return $sessionHelper;
        },  
      ),
    ),                
    
);
