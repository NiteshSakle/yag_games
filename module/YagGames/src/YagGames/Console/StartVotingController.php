<?php

namespace YagGames\Console;

use Zend\Console\Request as ConsoleRequest;

class StartVotingController extends BaseConsoleController {

    public function indexAction() {
        $request = $this->getRequest();

        // Make sure that we are running in a console and the user has not tricked our
        // application into running this action from a public web server.
        if (!$request instanceof ConsoleRequest) {
            throw new \RuntimeException('You can only use this action from a console!');
        }

        $this->logger = $this->getServiceLocator()->get('YagGames\Logger');

        $this->process();

        echo "Contest Voting has started. \n";
    }

    public function process() {
        $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
        $contests = $contestMediaTable->getVotingReadyContests();

        $config = $this->getConfig();

        foreach ($contests as $contest) {
            if ($this->startVoting($contest)) {                
                //Sending Email To Admin
                $contest['main_site_url'] = $config['main_site']['url'];
                $this->sendEmail('Voting Started for - ' . $contest['name'], $config['to_address_email'], 'voting_started_admin', $contest);                 
            }
        }
    }

    private function startVoting($contestData) {
        $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');

        $contest = new \YagGames\Model\Contest();
        $contest->id = $contestData['id'];
        $contest->voting_started = 1;
        return $contestMediaTable->update($contest);
    }
}
