<?php

namespace YagGames\Console;

use Zend\Console\Request as ConsoleRequest;

class StartVotingController extends BaseConsoleController
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

    
    $this->process();

    echo "Sent email";
  }

  public function process()
  {
    $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
    $contests = $contestMediaTable->getWinnersToBeAnouncedContests();
    
    foreach ($contests as $contest) {
      if ($this->announceWinners($contest)) {      
        $contestEmails = $contestMediaTable->getContestArtistEmails($contest['id']);
        $this->sendEmail('Winners for contest - ' . $contest['name'], $contestEmails, 'winners_announced', $contest);
      }
    }    
  }
  
  private function announceWinners($contest)
  {
    return true;    
  }

}
