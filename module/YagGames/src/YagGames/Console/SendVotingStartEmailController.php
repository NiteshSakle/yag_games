<?php

namespace YagGames\Console;

use Zend\Console\Request as ConsoleRequest;

class SendVotingStartEmailController extends BaseConsoleController {

    public function indexAction() {
        $request = $this->getRequest();

        // Make sure that we are running in a console and the user has not tricked our
        // application into running this action from a public web server.        
        if (!$request instanceof ConsoleRequest) {
            throw new \RuntimeException('You can only use this action from a console!');
        }

        $this->logger = $this->getServiceLocator()->get('YagGames\Logger');
        $contestId = (int) $request->getParam('contestId') ? (int) $request->getParam('contestId') : 0;
        
        $this->process($contestId);

        echo "Sent email. \n";
    }

    public function process($contestId) {
        if(!$contestId) {
            $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
            $contests = $contestMediaTable->getContestsForEmailSending();
            foreach ($contests as $contest) {
                $this->sendEmailToArtist($contest['id']);
            }
        } else {
            $this->sendEmailToArtist($contestId);
        }
    }    
    
    private function sendEmailToArtist($contestId){
        $config = $this->getConfig();        
        $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
        $contestArtists = $contestMediaTable->getContestArtistData($contestId);                
        
        foreach ($contestArtists as $contestArtist) {
            $contestArtist['main_site_url'] = $config['main_site']['url'];
            $contestArtist['user_data'] = $contestArtist;
            $this->sendEmail('Voting Started for - ' . $contestArtist['name'], $contestArtist['email'], 'voting_started', $contestArtist);
        }
    }
}
