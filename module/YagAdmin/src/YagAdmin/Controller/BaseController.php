<?php

namespace YagAdmin\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class BaseController extends AbstractActionController
{

  public $config;
  public $session;
  protected $logger;
  protected $settings;
  protected $mailer;

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

  public function printAndLog($msg)
  {
    $this->logger->debug($msg);
    return $msg;
  }
  
  public function getMailer()
  {
    if (!isset($this->settings)) {
      $this->getSettings();

      $options = array(
          'host' => $this->settings['smtp_host'],
          'connection_class' => 'plain',
          'port' => $this->settings['smtp_port'],
          'connection_config' => array(
              'username' => $this->settings['smtp_username'],
              'password' => $this->settings['smtp_password'],
              'ssl' => 'tls'
          ),
      );

      $transport = new \Zend\Mail\Transport\Smtp();
      $transport->setOptions(new \Zend\Mail\Transport\SmtpOptions($options));

      $this->mailer = new \YagGames\Channel\Mail($transport, $this->getServiceLocator()->get('YagGames\Logger'));
    }

    return $this->mailer;
  }

  public function getSettings()
  {
    if (!isset($this->settings)) {
      $settingsTable = $this->getServiceLocator()->get('YagGames\Model\SettingsTable');
      $this->settings = $settingsTable->fetchAll();
    }

    return $this->settings;
  }
  
  public function sendEmail($subject, $toEmail, $template, $data)
  {
    $mailer = $this->getMailer();
    $config = $this->getServiceLocator()->get('Config');
    try {
      $body = $mailer->getMailBody($template, $data);
      
      if (is_array($toEmail)) {
        foreach ($toEmail as $email) {
          $mailer->send($config['from_address_email'], $email, $subject, $body);
        }
      } else {
        $mailer->send($config['from_address_email'], $toEmail, $subject, $body);
      }

      return true;
    } catch (\Exception $e) {
      return false;
    }
  }
  
}
