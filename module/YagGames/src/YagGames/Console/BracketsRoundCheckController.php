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
                $contestMedia = $contestMediaTable->fetchAllByContest($record['contest_id']);
                $mediaCount = count($contestMedia);

                shuffle($contestMedia); // Shuffle the array for more randomization                
                $comboCount = (int) ($mediaCount / 2); // Should be 32;                
                $orphanMediaKey = 0;

                if (!($mediaCount % 2 == 0)) {
                    $orphanMediaKey = array_rand($contestMedia, 1);
                    $orphanMedia = $contestMedia[$orphanMediaKey];
                    unset($contestMedia[$orphanMediaKey]);
                }

                $bracketMediaCombo = new \YagGames\Model\ContestBracketMediaCombo();
                $bracketMediaCombo->contest_id = $record['contest_id'];
                $bracketMediaCombo->round = 1;

                for ($i = 1; $i <= $comboCount; $i++) {
                    $bracketMediaCombo->combo_id = $i;
                    $randomMedia = array_rand($contestMedia, 2);
                    $bracketMediaCombo->contest_media_id1 = $contestMedia[$randomMedia[0]]['id'];
                    $bracketMediaCombo->contest_media_id2 = $contestMedia[$randomMedia[1]]['id'];

                    $contestBracketMediaComboTable->insert($bracketMediaCombo);
                    // To avoid duplicates unset inserted media
                    unset($contestMedia[$randomMedia[0]], $contestMedia[$randomMedia[1]]);
                }

                //Insert orphan media
                if (isset($orphanMedia)) {
                    $bracketMediaCombo->combo_id = $i;
                    $bracketMediaCombo->contest_media_id1 = $orphanMedia['id'];
                    $bracketMediaCombo->contest_media_id2 = 0;

                    $contestBracketMediaComboTable->insert($bracketMediaCombo);
                    $i++;
                }

                if (!($i == 33)) {
                    $zeroMediaCount = (33 - $i);
                    for ($j = 1; $j <= $zeroMediaCount; $j++, $i++) {
                        $bracketMediaCombo->combo_id = $i;
                        $bracketMediaCombo->contest_media_id1 = 0;
                        $bracketMediaCombo->contest_media_id2 = 0;

                        $contestBracketMediaComboTable->insert($bracketMediaCombo);
                    }
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
            return "FINAL 4";
        } elseif ($round == 4) {
            return "ELITE 8";
        } elseif ($round == 3) {
            return "SWEET 16";
        } elseif ($round == 2) {
            return "TOP 32";
        } else {
            return "";
        }
    }

}
