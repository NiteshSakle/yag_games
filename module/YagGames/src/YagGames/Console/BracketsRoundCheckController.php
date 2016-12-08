<?php

namespace YagGames\Console;

use Zend\Console\Request as ConsoleRequest;

class BracketsRoundCheckController extends BaseConsoleController
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

        echo "Brackets Game Round Check";
    }

    private function process()
    {
        $contestBracketRoundTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketRoundTable');
        $contestBracketMediaComboTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketMediaComboTable');
        $records = $contestBracketRoundTable->fetchAllActiveContests();
        $this->config = $this->getConfig();

        $today = new \DateTime(date("Y-m-d"));
        foreach ($records as $record) {
            //Round 1 - Special Case we won't consider votes here
            $roundDate = new \DateTime($record["round1"]);

            if (empty($record['current_round']) && $roundDate <= $today) {
                $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
                $contestMediaRecords = $contestMediaTable->fetchAllByContest($record['contest_id']);
                
                $contestMedia = array();
                foreach ($contestMediaRecords as $mediaRecord) {
                    $contestMedia[] = $mediaRecord['id'];
                }
                
                $mediaCount = count($contestMedia);
                $diffMediaCount = 64 - $mediaCount;
                $dummyMedia = array();
                // Insert dummy media if contest is not full
                for ($i = 1 ; $i <= $diffMediaCount; $i++) {
                    $dummyMedia[] = 0;
                }               
                
                if ($mediaCount >= $diffMediaCount) {
                    $iterator = $contestMedia;
                    $nonIterator = $dummyMedia;
                } else {
                    $iterator = $dummyMedia;
                    $nonIterator = $contestMedia;
                }
                unset($contestMedia, $dummyMedia);                
                $comboMedia = array();                
                shuffle($iterator);
                shuffle($nonIterator);
                
                // Create ComboMedia
                for($i = 0, $j = 0; $j < 32;) {
                    $nonIteratorCount = count($nonIterator);                    
                    if ($nonIteratorCount > 0) {                        
                        $randKey = array_rand($nonIterator, 1);
                        $comboMedia[$j][0] = $iterator[$i];                        
                        $comboMedia[$j][1] = $nonIterator[$randKey];
                        unset($nonIterator[$randKey]);
                        $i = $i + 1;
                    } else {                        
                        $comboMedia[$j][0] = $iterator[$i];                        
                        $comboMedia[$j][1] = $iterator[$i + 1];
                        $i = $i + 2;
                    }                  
                    $j++;
                }                
                shuffle($comboMedia); // Shuffle the array for more randomization
                
                $bracketMediaCombo = new \YagGames\Model\ContestBracketMediaCombo();
                $bracketMediaCombo->contest_id = $record['contest_id'];
                $bracketMediaCombo->round = 1;

                for ($i = 0; $i < 32; $i++) {
                    $bracketMediaCombo->combo_id = $i+1;
                    $bracketMediaCombo->contest_media_id1 = $comboMedia[$i][0];
                    $bracketMediaCombo->contest_media_id2 = $comboMedia[$i][1];                            

                    $contestBracketMediaComboTable->insert($bracketMediaCombo);                    
                }
                // Update Current Round
                $this->updateContestround($record['contest_id'], 1);
                $record['current_round'] = 1;
            }

            // From round 2 - need to consider number of votes recieved
            for ($round = 2; $round <= 6; $round++) {
                $roundDate = new \DateTime($record["round" . $round]);

                if (($round - 1) == $record['current_round'] && $roundDate <= $today) {
                    $roundWinners = $contestBracketMediaComboTable->getTopRatedMediaForNextRound($record['contest_id'], $round - 1);
                    $bracketMediaCombo = new \YagGames\Model\ContestBracketMediaCombo();
                    $bracketMediaCombo->contest_id = $record['contest_id'];
                    $bracketMediaCombo->round = $round;

                    for ($j = 0, $i = 1; $j < count($roundWinners) - 1; $j = $j + 2, $i++) {
                        $bracketMediaCombo->combo_id = $i;
                        $bracketMediaCombo->contest_media_id1 = $roundWinners[$j]['next_round_media_id'];
                        $bracketMediaCombo->contest_media_id2 = isset($roundWinners[$j + 1]) ? $roundWinners[$j + 1]['next_round_media_id'] : 0;
                        $contestBracketMediaComboTable->insert($bracketMediaCombo);

                        $this->nextRoundQualifiedEmail($bracketMediaCombo->contest_media_id1, $round);
                        $this->nextRoundQualifiedEmail($bracketMediaCombo->contest_media_id2, $round);
                    }

                    $this->updateContestround($record['contest_id'], $round);
                    // Pending Rounds Get Fired
                    $record['current_round'] = $round;
                }
            }
        }
    }

    private function updateContestround($contestId, $round)
    {
        $contestBracketRoundTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketRoundTable');
        $contestRound = array();
        $contestRound['contest_id'] = $contestId;
        $contestRound['current_round'] = $round;

        return $contestBracketRoundTable->updateByContestId($contestRound, $contestId);
    }

    private function nextRoundQualifiedEmail($contestMediaId, $round)
    {
        $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
        $contestMediaData = $contestMediaTable->getContestMediaDetails($contestMediaId);
        if (!$contestMediaData) {
            return $this->printAndLog("No contest media found");
        }
        $contestMediaData['round_name'] = $this->getRoundName($round);
        $contestMediaData['main_site_url'] = $this->config['main_site']['url'];
        
        $this->sendEmail('Congratulations! You are one of the ' . $contestMediaData['round_name'] . '.', $contestMediaData['email'], 'bracket_game_round' . $round . '_qualified', $contestMediaData);
    }

    private function getRoundName($round)
    {
        if ($round == 6) {
            return "CHAMPION";
        } elseif ($round == 5) {
            return "CORE 4";
        } elseif ($round == 4) {
            return "GREAT 8";
        } elseif ($round == 3) {
            return "SUPER 16";
        } elseif ($round == 2) {
            return "TOP 32";
        } else {
            return "";
        }
    }

}
