<?php

namespace YagGames\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class BaseController extends AbstractActionController
{

  public $config;
  public $session;

  public function onDispatch(\Zend\Mvc\MvcEvent $e)
  {
    $this->session = $this->sessionPlugin();
    return parent::onDispatch($e);
  }

  public function checkLogin()
  {
    if (!isset($this->session->mem_id)) {
      throw new \Exception('Please login', 401);
    }
  }

  protected function getConfig()
  {
    if (!isset($this->config)) {
      $this->config = $this->getServiceLocator()->get('config');
    }

    return $this->config;
  }
  
  protected function getSession()
  {
    if (!isset($this->session)) {
      $this->session = $this->sessionPlugin();
    }

    return $this->session;
  }

}
