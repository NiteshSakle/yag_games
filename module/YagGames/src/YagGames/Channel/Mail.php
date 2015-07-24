<?php

namespace YagGames\Channel;

use Zend\View\Model\ViewModel;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\TemplatePathStack;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Log\Logger;

class Mail
{

  protected $transport;
  protected $logger;

  public function __construct(SmtpTransport $transport, Logger $logger)
  {
    $this->transport = $transport;
    $this->logger = $logger;
  }

  public function send($from, $to, $subject, $body)
  {
    try {
      $message = new Message();
      $message->addTo($to)
              ->setFrom($from)
              ->setSubject($subject)
              ->setBody($body)
              ->setEncoding('UTF-8');

      $message->getHeaders()->get('content-type')->setType('multipart/alternative');

      $this->transport->send($message);

      return true;
    } catch (\Exception $e) {
      $this->logger->err('Error in sending Mail: ' . $e->getMessage());
      return false;
    }
  }

  public function getMailBody($template, $data)
  {
    $content = $this->getContentFromTemplate($template, $data);
    return $this->getMailBodyFromHtml($content);
  }

  public function getContentFromTemplate($template, $data)
  {
    $view = new PhpRenderer();
    $view->getHelperPluginManager()->get('basePath')->setBasePath('');

    $resolver = new TemplatePathStack();
    $resolver->setPaths(array(
        'mailTemplate' => __DIR__ . '/../../../view/email'
    ));
    $view->setResolver($resolver);

    $viewModel = new ViewModel();
    $viewModel->setTemplate($template)
            ->setVariables($data);

    return $view->render($viewModel);
  }

  public function getMailBodyFromHtml($content)
  {
    $text = new MimePart(strip_tags(str_replace(array("<br />", "<br/>", "<br>"), '\n', $content)));
    $text->type = "text/plain";

    $html = new MimePart($content);
    $html->type = "text/html";

    $body = new MimeMessage();
    $body->setParts(array($text, $html));

    return $body;
  }

}
