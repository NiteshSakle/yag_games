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
                    $contest['contest_type'] = $this->getRouteName($contest['type_id']);
                    $this->sendEmail('Winners for contest - ' . $contest['name'], $contestArtist['email'], 'winners_announced', $contest);
                    //$mailer->send($config['from_address_email'], $email, $subject, $body);
                }
                
                //Email to Admin
                $this->sendEmail('Winners for contest - ' . $contest['name'], 'info@yourartgallery.com', 'winners_announced_admin', $contest);
            }
        }
    }

    private function announceWinners($contestData) {

        if ($contestData['type_id'] == 3) {

            $contestBracketMediaComboTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketMediaComboTable');

            $winner = $contestBracketMediaComboTable->getTopRatedMediaForNextRound($contestData['id'], 6);           
           
            if ($this->updateBracketGameWinners($winner)) {
                $this->updateContestround($contestData['id'], 7);

                $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
                $contestMedia = $contestMediaTable->getContestMediaDetails($winner[0]['next_round_media_id']);

                $this->awardTropyToWinner(array('media_id' => $contestMedia['media_id'], 'owner' => $contestMedia['owner']), $contestData);

                return TRUE;
            }
            
            return FALSE;
        } else {

            $contestMediaRatingTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaRatingTable');
            $winners = $contestMediaRatingTable->getTop10RatedMedia($contestData['id']);

            $contestWinnerTable = $this->getServiceLocator()->get('YagGames\Model\ContestWinnerTable');
            foreach ($winners as $key => $winner) {
                $rank = $key + 1;
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
    
    private function updateContestround($contestId, $round) {
        $contestBracketRoundTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketRoundTable');        
        $contestBracketRound = array();
        $contestBracketRound['contest_id'] = $contestId;
        $contestBracketRound['current_round'] = $round;

        return $contestBracketRoundTable->updateByContestId($contestBracketRound, $contestId);
    }
    
    private function updateBracketGameWinners($winner) {        
        $contestWinnerTable = $this->getServiceLocator()->get('YagGames\Model\ContestWinnerTable');
        $contestBracketMediaComboTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketMediaComboTable');        
        $combo_details = $contestBracketMediaComboTable->fetchContestComboDetails($winner[0]['contest_id']);
        
        $winners = array();
        if (count($winner)) {
            $winners[] = $winner[0]['next_round_media_id'];
            for ($r=6;$r>=3;$r--) {
                for($i=0;$i<count($combo_details[$r]);$i++){
                    if(!in_array($combo_details[$r][$i]['contest_media_id1'],$winners,TRUE)){
                        $winners[] = $combo_details[$r][$i]['contest_media_id1'];
                    } elseif (!in_array($combo_details[$r][$i]['contest_media_id2'],$winners,TRUE)) {
                        $winners[] = $combo_details[$r][$i]['contest_media_id2'];
                    }
                }
            }
        
        
            $contestWinner = new \YagGames\Model\ContestWinner();
            $contestWinner->contest_media_id = $winner[0]['next_round_media_id'];
            $contestWinner->rank = 1;
            $contestWinner->no_of_votes = ($winner[0]['next_round_media_id'] == $winner[0]['contest_media_id1']) ? $winner[0]['cmediaid1_votes'] : $winner[0]['cmediaid2_votes'];
            $contestWinnerTable->insert($contestWinner);

            foreach ($winners as $key => $winner) {
                if($key != 0 ) {
                    $rank = $key + 1;
                    $contestWinner = new \YagGames\Model\ContestWinner();
                    $contestWinner->contest_media_id = $winners[$key];
                    $contestWinner->rank = $rank;
                    $contestWinner->no_of_votes = 0;

                    $contestWinnerTable->insert($contestWinner);            
                }
            }

            return true;        
        }
    }
    
    private function getRouteName($contestTypeId) {
        switch ($contestTypeId) {
            case 1:
                $contestType = 'photo-contest';
                break;
            case 2:
                $contestType = 'fan-favorite';
                break;
            case 3:
                $contestType = 'brackets';
                break;
            default :
                $contestType = 'photo-contest';
                break;
        }
        return $contestType;
    }

}
