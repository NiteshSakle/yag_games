<?php

namespace YagGames\Console;

use Zend\Console\Request as ConsoleRequest;

class SendSuccessSubmissionEmailController extends BaseConsoleController
{

  public function indexAction()
  {
    $request = $this->getRequest();

    // Make sure that we are running in a console and the user has not tricked our
    // application into running this action from a public web server.
    if (!$request instanceof ConsoleRequest) {
      throw new \RuntimeException('You can only use this action from a console!');
    }

    $this->logger = $this->getServiceLocator()->get('YagGames\Logger');

    $contestMediaId = $request->getParam('contestMediaId');
    if (!$contestMediaId) {
      return $this->printAndLog("Contest media id Missing\n");
    }

    $this->process($contestMediaId);

    echo "Sent email";
  }

  public function process($contestMediaId)
  {
    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
    $contestMediaData = $contestMediaTable->getContestMediaDetails($contestMediaId);
    if (!$contestMediaData) {
      return $this->printAndLog("No contest media found");
    }
    
    $this->sendEmail('Thank You For Entering', $contestMediaData['email'], 'submission', $contestMediaData);
  }

}
