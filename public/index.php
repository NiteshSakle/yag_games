<?php
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

session_start();
if(isset($_SESSION['HA::CONFIG']) || isset($_SESSION['HA::STORE']) || isset($_SESSION['FlashMessenger']) ) {
    unset($_SESSION['HA::CONFIG']);
    unset($_SESSION['HA::STORE']);
    unset($_SESSION['FlashMessenger']);
}

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server') {
    $path = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (__FILE__ !== $path && is_file($path)) {
        return false;
    }
    unset($path);
}

// Setup autoloading
require 'init_autoloader.php';

// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.php')->run();
