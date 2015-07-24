<?php

namespace YagAdmin\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class BaseController extends AbstractActionController
{

  public $config;
  public $session;

  public function onDispatch(\Zend\Mvc\MvcEvent $e)
  {
    $this->session = $this->adminSessionPlugin();
    return parent::onDispatch($e);
  }

  public function checkLogin()
  {
    if (!isset($this->session->admin_id)) {
      throw new \Exception('Please login', 403);
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
      $this->session = $this->adminSessionPlugin();
    }

    return $this->session;
  }

}
