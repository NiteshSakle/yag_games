<?php

namespace YagGames\Console;

use Zend\Console\Request as ConsoleRequest;

class AnnounceWinnersController extends BaseConsoleController
{

    protected $membershipService;
    protected $couponService;
    protected $mediaImage;
    protected $ordinal;
    protected $config;
    protected $kCrypt;

    function __construct($membershipService, $couponService, $mediaImage, $ordinal, $kCrypt)
    {
        $this->membershipService = $membershipService;
        $this->couponService = $couponService;
        $this->mediaImage = $mediaImage;
        $this->ordinal = $ordinal;
        $this->kCrypt = $kCrypt;
    }

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
        $contestWinnerTable = $this->getServiceLocator()->get('YagGames\Model\ContestWinnerTable');
        $promotionsTable = $this->getServiceLocator()->get('YagGames\Model\PromotionsTable');
        $this->config = $this->getConfig();

        foreach ($contests as $contest) {
            if ($this->announceWinners($contest)) {
                $contestArtists = $contestMediaTable->getContestArtistData($contest['id']);
                $contest['main_site_url'] = $this->config['main_site']['url'];
                foreach ($contestArtists as $contestArtist) {
                    $contest['user_data'] = $contestArtist;
                    $contest['contest_type'] = $this->getRouteName($contest['type_id']);
                    $this->sendEmail('Winners for contest - ' . $contest['name'], $contestArtist['email'], 'winners_announced', $contest);
                    //$mailer->send($this->config['from_address_email'], $email, $subject, $body);
                }
                // Process benefits of top 5 winners
                $this->processTopWinnersBenefits($contest, $contestArtists, $contestWinnerTable, $promotionsTable);
                //Email to Admin
                $this->sendEmail('Winners for contest - ' . $contest['name'], 'info@yourartgallery.com', 'winners_announced_admin', $contest);
                //Update Contest Record
                $contestData = array();
                $contestData['winners_announced'] = 1;
                $contestMediaTable->updateSpecificFields($contest['id'], $contestData);
            }
        }
    }

    private function announceWinners($contestData)
    {

        if ($contestData['type_id'] == 3) {

            $contestBracketMediaComboTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketMediaComboTable');

            $winner = $contestBracketMediaComboTable->getTopRatedMediaForNextRound($contestData['id'], 6);

            if ($this->updateBracketGameWinners($winner, $contestData)) {
                $this->updateContestround($contestData['id'], 7);
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

    private function awardTropyToWinner($winner, $contestData)
    {
        $monthlyAwardTable = $this->getServiceLocator()->get('YagGames\Model\MonthlyAwardTable');

        $monthlyAward = new \YagGames\Model\MonthlyAward();
        $monthlyAward->contest_id = $contestData['id'];
        $monthlyAward->media_id = $winner['media_id'];
        $monthlyAward->award_type = 5;
        $monthlyAward->date = new \Zend\Db\Sql\Expression('NOW()');
        $monthlyAward->member_id = $winner['owner'];

        return $monthlyAwardTable->insert($monthlyAward);
    }

    private function updateContestround($contestId, $round)
    {
        $contestBracketRoundTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketRoundTable');
        $contestBracketRound = array();
        $contestBracketRound['contest_id'] = $contestId;
        $contestBracketRound['current_round'] = $round;

        return $contestBracketRoundTable->updateByContestId($contestBracketRound, $contestId);
    }

    private function updateBracketGameWinners($winner, $contestData)
    {
        $contestWinnerTable = $this->getServiceLocator()->get('YagGames\Model\ContestWinnerTable');
        $contestBracketMediaComboTable = $this->getServiceLocator()->get('YagGames\Model\ContestBracketMediaComboTable');
        $combo_details = $contestBracketMediaComboTable->fetchContestComboDetails($winner[0]['contest_id']);

        $winners = array();
        if (count($winner)) {
            $winners[] = $winner[0]['next_round_media_id'];
            for ($r = 6; $r >= 3; $r--) {
                for ($i = 0; $i < count($combo_details[$r]); $i++) {
                    if (!in_array($combo_details[$r][$i]['contest_media_id1'], $winners, TRUE)) {
                        $winners[] = $combo_details[$r][$i]['contest_media_id1'];
                    } elseif (!in_array($combo_details[$r][$i]['contest_media_id2'], $winners, TRUE)) {
                        $winners[] = $combo_details[$r][$i]['contest_media_id2'];
                    }
                }
            }

            foreach ($winners as $key => $brtwinner) {
                $rank = $key + 1;
                $contestWinner = new \YagGames\Model\ContestWinner();
                $contestWinner->contest_media_id = $winners[$key];
                $contestWinner->rank = $rank;
                if ($rank == 1) {
                    $contestWinner->no_of_votes = ($winner[0]['next_round_media_id'] == $winner[0]['contest_media_id1']) ? $winner[0]['cmediaid1_votes'] : $winner[0]['cmediaid2_votes'];
                } else {
                    $contestWinner->no_of_votes = 0;
                }

                if ($contestWinnerTable->insert($contestWinner) && $rank <= 4) {
                    $this->awardTrophiesForBracket($winners[$key], $contestData);
                }
            }
            return true;
        }
    }

    private function awardTrophiesForBracket($contestMediaId, $contestData)
    {
        $contestMediaTable = $this->getServiceLocator()->get('YagGames\Model\ContestMediaTable');
        $contestMedia = $contestMediaTable->getContestMediaDetails($contestMediaId);

        $this->awardTropyToWinner(array('media_id' => $contestMedia['media_id'], 'owner' => $contestMedia['owner']), $contestData);
    }

    private function getRouteName($contestTypeId)
    {
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

    private function processTopWinnersBenefits($contest, $contestArtists, $contestWinnerTable, $promotionsTable)
    {
        if ($contest['winners_announced'] != 1) {

            if ($contest['type_id'] == 1) { //Photo Contest fetch top 5 winners
                $contestTopWinners = $contestWinnerTable->fetchAllWinnersOfContest($contest['id'], 5);
            } else if ($contest['type_id'] == 3) { // Brackets fetch top 4 winners
                $contestTopWinners = $contestWinnerTable->fetchAllWinnersOfContest($contest['id'], 4);
            }

            $data = array();
            $data['totalEntries'] = count($contestArtists);
            $data['awsPath'] = $this->config['aws']['path'];
            $data['mediaImage'] = $this->mediaImage;
            $data['ordinal'] = $this->ordinal;
            $data['kCrypt'] = $this->kCrypt;
            $data['contestTopWinners'] = $contestTopWinners;
            foreach ($contestTopWinners as $key => $winner) {
                $data['contest'] = $contest;
                $data['winner'] = $winner;

                if ($contest['type_id'] == 1) { // Photo Contest
                    // Generate Coupon Code And Insert Into Promotions Table
                    if ($winner['rank'] == 1) { //Winner
                        $promotionsModel = $this->couponService->generateWinnersCoupon($contest, $winner['owner'], $winner['rank'], 1);
                    } else { //Runner Up
                        $promotionsModel = $this->couponService->generateWinnersCoupon($contest, $winner['owner'], $winner['rank'], 2);
                    }

                    if ($promotionsModel) {
                        $insertPromotionsData = $promotionsTable->insert($promotionsModel);
                        if ($insertPromotionsData) {
                            $data['promoCode'] = $promotionsModel->promo_code;
                            if ($winner['rank'] == 1) {
                                //Upgrade Membership to Platinum for next 6 months
                                $newMsExpDate = new \DateTime('+6 months', new \DateTimeZone('GMT'));
                                $upgradeMebership = $this->membershipService->upgradeToPlatinumMembership($winner['owner'], $newMsExpDate);
                                //Send Coupon Details
                                $this->sendEmail('Congratulations! You are the FIRST PLACE winner of our ' . $contest['name'], $winner['email'], 'contest_winner', $data);
                            } else {
                                //Send Coupon Details
                                $this->sendEmail('Congratulations! You are the RUNNER UP of our ' . $contest['name'], $winner['email'], 'contest_runnerup', $data);
                            }
                        } else {
                            echo 'Error Occured While Inserting Contest Coupon Data Into Promotions Table Of The User:' . $winner['owner'] . "\n";
                        }
                    }
                } else if ($contest['type_id'] == 3) {  // Brackets
                    if ($winner['rank'] == 1 || $winner['rank'] == 2) {
                        // Champion or 2nd Position
                        $promotionsModel1 = $this->couponService->generateWinnersCoupon($contest, $winner['owner'], $winner['rank'], 1);
                        $promotionsModel2 = $this->couponService->generateWinnersCoupon($contest, $winner['owner'], $winner['rank'], 2);
                        if ($promotionsModel1 && $promotionsModel2) {
                            $insertPromotionsData1 = $promotionsTable->insert($promotionsModel1);
                            $insertPromotionsData2 = $promotionsTable->insert($promotionsModel2);

                            if ($insertPromotionsData1 && $insertPromotionsData2) {
                                $data['promoCode1'] = $promotionsModel1->promo_code;
                                $data['promoCode2'] = $promotionsModel2->promo_code;
                                if ($winner['rank'] == 1) {
                                    //$250 off coupon generation
                                    $promotionsModel3 = $this->couponService->generateWinnersCoupon($contest, $winner['owner'], $winner['rank'], 3);
                                    if ($promotionsTable->insert($promotionsModel3)) {
                                        $data['promoCode3'] = $promotionsModel3->promo_code;
                                    }

                                    // Only for Champion - Upgrade Membership to Platinum for next 6 months
                                    $newMsExpDate = new \DateTime('+6 months', new \DateTimeZone('GMT'));
                                    $upgradeMebership = $this->membershipService->upgradeToPlatinumMembership($winner['owner'], $newMsExpDate);

                                    //Send Coupon Details
                                    $this->sendEmail('Congratulations! You are the CHAMPION of our ' . $contest['name'], $winner['email'], 'bracket_game_winner', $data);
                                } else {
                                    //Send Coupon Details
                                    $this->sendEmail('Congratulations! You are the SECOND PLACE winner of our ' . $contest['name'], $winner['email'], 'bracket_game_2nd_winner', $data);
                                }
                            } else {
                                echo 'Error Occured While Inserting Contest Coupon Data Into Promotions Table Of The User:' . $winner['owner'] . "\n";
                            }
                        }
                    } else if ($winner['rank'] == 3 || $winner['rank'] == 4) {
                        // 3rd or 4th Position   
                        $promotionsModel = $this->couponService->generateWinnersCoupon($contest, $winner['owner'], $winner['rank'], 2);
                        if ($promotionsModel) {
                            $insertPromotionsData = $promotionsTable->insert($promotionsModel);
                            if ($insertPromotionsData) {
                                $data['promoCode'] = $promotionsModel->promo_code;
                                //Send Coupon Details
                                $this->sendEmail('Congratulations! You are one of the CORE FOUR winners of our ' . $contest['name'], $winner['email'], 'bracket_game_runnerup', $data);
                            } else {
                                echo 'Error Occured While Inserting Contest Coupon Data Into Promotions Table Of The User:' . $winner['owner'] . "\n";
                            }
                        }
                    }
                }
            }
        }
    }

}
