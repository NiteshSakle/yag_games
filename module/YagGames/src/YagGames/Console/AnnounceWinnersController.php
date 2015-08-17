<?php

namespace YagGames\Console;

use Zend\Console\Request as ConsoleRequest;

class AnnounceWinnersController extends BaseConsoleController {

    public function indexAction() {
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

    public function process() {
        $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestTable');
        $contests = $contestMediaTable->getWinnersToBeAnouncedContests();

        $config = $this->getConfig();

        foreach ($contests as $contest) {
            if ($this->announceWinners($contest)) {
                $contestArtists = $contestMediaTable->getContestArtistData($contest['id']);
                $contest['main_site_url'] = $config['main_site']['url'];
                foreach ($contestArtists as $contestArtist) {
                    $contest['user_data'] = $contestArtist;
                    $this->sendEmail('Winners for contest - ' . $contest['name'], $contestArtist['email'], 'winners_announced', $contest);
                    //$mailer->send($config['from_address_email'], $email, $subject, $body);
                }
            }
        }
    }

    private function announceWinners($contestData) {
        $contestMediaRatingTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaRatingTable');
        $winners = $contestMediaRatingTable->getTop10RatedMedia($contestData['id']);

        $contestWinnerTable = $this->getServiceLocator()->get('YagGames\Model\ContestWinnerTable');
        foreach ($winners as $key => $winner) {
            $rank = $key+1;
            $contestWinner = new \YagGames\Model\ContestWinner();
            $contestWinner->contest_media_id = $winner['contest_media_id'];
            $contestWinner->rank = $rank;

            if ($contestWinnerTable->insert($contestWinner)) {
                if ((int) $rank === 1) {
                    $this->awardTropyToWinner($winner, $contestData);
                }
            }
        }

        if (count($winners)) {
            return true;
        }

        return false;
    }

    private function awardTropyToWinner($winner, $contestData) {
        $monthlyAwardTable = $this->getServiceLocator()->get('YagGames\Model\MonthlyAwardTable');

        $monthlyAward = new \YagGames\Model\MonthlyAward();
        $monthlyAward->contest_id = $contestData['id'];
        $monthlyAward->media_id = $winner['media_id'];
        $monthlyAward->award_type = 5;
        $monthlyAward->date = new \Zend\Db\Sql\Expression('NOW()');
        $monthlyAward->member_id = $winner['owner'];

        return $monthlyAwardTable->insert($monthlyAward);
    }

}