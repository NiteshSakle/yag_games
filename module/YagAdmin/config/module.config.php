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
    
    'router' => array(
        'routes' => array(
            'yagadmin' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/yagadmin',
                    'defaults' => array(
                        '__NAMESPACE__' => 'YagAdmin\Controller',
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
            'yag-admin/layout/layout'   => __DIR__ . '/../view/yagadmin/layout/layout.phtml',
            'yag-admin/index/index'     => __DIR__ . '/../view/yagadmin/index/index.phtml',
        ),
        'template_path_stack' => array(
            'yagadmin' => __DIR__ . '/../view',
        ),
    )
    
);
