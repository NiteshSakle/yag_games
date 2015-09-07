<?php

namespace YagGames\Console;

use Zend\Console\Request as ConsoleRequest;

class BracketsRoundCheckController extends BaseConsoleController {
    
    public function indexAction() {
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
    
    private function process() {

        $contestBracketRoundTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketRoundTable');
        $records = $contestBracketRoundTable->fetchAll();

        $today = new \DateTime(date("Y-m-d"));

        foreach ($records as $record) {

            //Round 1 - Special Case we won't consider votes here
            $roundDate = new \DateTime($record["round1"]);

            if ($today == $roundDate) {

                $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
                $contestMedia = $contestMediaTable->fetchAllByContest($record['contest_id']);

                $contestBracketMediaComboTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketMediaComboTable');

                shuffle($contestMedia); // Shuffle the array for more randomization                              
                
                $mediaCount = count($contestMedia);
                
                $comboCount = (int) ($mediaCount/2); // Should be 32;

                $orphanMediaKey = 0;

                if (!($mediaCount % 2 == 0)) {

                    $orphanMediaKey = array_rand($contestMedia, 1);
                    $orphanMedia = $contestMedia[$orphanMediaKey];

                    unset($contestMedia[$orphanMediaKey]);

                    $comboCount = $comboCount - 1;
                }

                $i = 1;

                for ($i; $i <= $comboCount; $i++) {

                    $bracketMediaCombo = new \YagGames\Model\ContestBracketMediaCombo();

                    $randomMedia = array_rand($contestMedia, 2);

                    $bracketMediaCombo->combo_id = $i;
                    $bracketMediaCombo->contest_id = $record['contest_id'];
                    $bracketMediaCombo->contest_media_id1 = $contestMedia[$randomMedia[0]]['id'];
                    $bracketMediaCombo->contest_media_id2 = $contestMedia[$randomMedia[1]]['id'];
                    $bracketMediaCombo->round = 1;

                    $contestBracketMediaComboTable->insert($bracketMediaCombo);

                    // To avoid duplicates unset inserted media
                    unset($contestMedia[$randomMedia[0]], $contestMedia[$randomMedia[1]]);
                }

                //Insert orphan media
                if (isset($orphanMedia)) {

                    $bracketMediaCombo = new \YagGames\Model\ContestBracketMediaCombo();
                    
                    $bracketMediaCombo->combo_id = $i;
                    $bracketMediaCombo->contest_id = $record['contest_id'];
                    $bracketMediaCombo->contest_media_id1 = $orphanMedia['id'];
                    $bracketMediaCombo->contest_media_id2 = 0;
                    $bracketMediaCombo->round = 1;

                    $contestBracketMediaComboTable->insert($bracketMediaCombo);
                    
                    $i++;
                }

                if (!($i == 33)) {

                    $zeroMediaCount = (33 - $i);

                    for ($j = 1; $j <= $zeroMediaCount; $j++) {

                        $bracketMediaCombo = new \YagGames\Model\ContestBracketMediaCombo();

                        $bracketMediaCombo->combo_id = $i;
                        $bracketMediaCombo->contest_id = $record['contest_id'];
                        $bracketMediaCombo->contest_media_id1 = 0;
                        $bracketMediaCombo->contest_media_id2 = 0;
                        $bracketMediaCombo->round = 1;

                        $contestBracketMediaComboTable->insert($bracketMediaCombo);
                        
                        $i++;
                    }
                }
            }
            
            // From round 2 - need to consider number of votes recieved
            for($round = 2; $round <= 6; $round++) {
                
                $roundDate = new \DateTime($record["round".$round]);
                
                if ($today == $roundDate) {
                   
                   $contestBracketMediaComboTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketMediaComboTable');
                   
                   $roundWinners = $contestBracketMediaComboTable->getTopRatedMediaForNextRound($record['contest_id'], $round - 1);
                   
                   $i = 1;                  
                   
                   for ($j = 0; $j < count($roundWinners) - 1; $j = $j+2) {

                        $bracketMediaCombo = new \YagGames\Model\ContestBracketMediaCombo();                       

                        $bracketMediaCombo->combo_id = $i;
                        $bracketMediaCombo->contest_id = $record['contest_id'];
                        $bracketMediaCombo->contest_media_id1 = $roundWinners[$j]['next_round_media_id'];

                        if (isset($roundWinners[$j+1])) {
                            $bracketMediaCombo->contest_media_id2 = $roundWinners[$j+1]['next_round_media_id'];
                        } else {
                            $bracketMediaCombo->contest_media_id2 = 0;
                        }

                        $bracketMediaCombo->round = $round;

                        $contestBracketMediaComboTable->insert($bracketMediaCombo);
                        
                        $i++;
                    }
                }
            }
        }               
    }

}
