<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

date_default_timezone_set("America/New_York");

return array(
    'service_manager' => array(
        'invokables' => array(
            'Zend\Session\SessionManager' => 'Zend\Session\SessionManager',
        ),
        'factories' => array(
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
        ),
    ),
    
    'module_layouts' => array(
        'YagGames' => 'layout/layout.phtml',
        'YagAdmin' => 'layout/admin-layout.phtml',
    ),
    
    'to_address_email' => 'info@yourartgallery.com',
    'from_address_email' => 'info@yourartgallery.com',
    'developers_email' => array(
        'venu@riktamtech.com',
    ),

);