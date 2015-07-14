<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace YagGames;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        // php settings
        $config = $e->getApplication()->getServiceManager()->get('Config');
        if (isset($config['phpSettings'])) {
            foreach($config['phpSettings'] as $key => $value) {
                ini_set($key, $value);
            }
        }
        
        //log errors
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onDispatchError'), 0);
        $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, array($this, 'onRenderError'), 0);

        //show proper message to user when fatal error occurs
        $module = $this;
        register_shutdown_function(function () use ($e, &$module) {
            $error = error_get_last();
            if (null !== $error && ($error['type'] === E_ERROR || $error['type'] === E_USER_ERROR)) {
                $module->errorHandler($e, $error);
                include dirname(__FILE__). '/view/error/500.phtml';
                die();
            }
        });
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
    public function getServiceConfig()
    {
        return include __DIR__ . '/config/service.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
       
    public function onDispatchError($e)
    {
        return $this->errorHandler($e, $e->getParam('exception'));
    }

    public function onRenderError($e)
    {
        return $this->errorHandler($e, $e->getParam('exception'));
    }
    
    public function logErrors($event, $exception)
    {
        if ($exception) {
            $sm = $event->getApplication()->getServiceManager();
            $service = $sm->get('YagGames\Logger');
            $service->err($exception);
        }
    }
    
    public function sendEmail($event, $exception)
    {
        $mailer = $event->getApplication()->getServiceManager()->get('Application\Channel\Mail');
        $config = $event->getApplication()->getServiceManager()->get('Config');

        try {
            $mailer->send(
                $config['from_address_email'],
                $config['developers_email'], 
                "Exception in Yag", 
                'exception', 
                array(
                    'exception' => $exception,
                )
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function errorHandler($event, $exception)
    {
        if ($exception) {
            $this->logErrors($event, $exception);
            //$this->sendEmail($event, $exception);
        }
    }
}
