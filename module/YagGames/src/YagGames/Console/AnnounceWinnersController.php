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
            
    function __construct($membershipService, $couponService, $mediaImage, $ordinal)
    {        
        $this->membershipService = $membershipService;
        $this->couponService = $couponService;
        $this->mediaImage = $mediaImage;
        $this->ordinal = $ordinal;                
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
                    $this->sendEmail('Winners for contest - ' . $contest['name'], $contestArtist['email'], 'winners_announced', $contest);
                    //$mailer->send($this->config['from_address_email'], $email, $subject, $body);
                }
                // Process benefits of top 5 winners
                $this->processTopWinnersBenefits($contest, $contestArtists, $contestWinnerTable, $promotionsTable);
                //Email to Admin
                $this->sendEmail('Winners for contest - ' . $contest['name'], 'info@yourartgallery.com', 'winners_announced_admin', $contest);
            }
        }
    }

    private function announceWinners($contestData)
    {
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

    private function processTopWinnersBenefits($contest, $contestArtists, $contestWinnerTable, $promotionsTable)
    {
        $contestTopWinners = $contestWinnerTable->fetchContestTopWinners($contest['id'], 5);
        $i = 1;
        $data = array();
        $data['totalEntries'] = count($contestArtists);

        $data['contestType'] = 'Photo Contest';
        $data['awsPath'] = $this->config['aws']['path'];
        $data['mediaImage'] = $this->mediaImage;
        $data['ordinal'] = $this->ordinal;
        foreach ($contestTopWinners as $key => $winner) {
            $otherWinners = $contestTopWinners;
            unset($otherWinners[$key]);
            $data['contest'] = $contest;
            $data['winner'] = $winner;
            $data['otherWinners'] = $otherWinners;
            // Prepare Promotions/Coupon Code/Promo Code Data And Insert Into Promotions Table
            $promotionsModel = $this->couponService->generateWinnersCoupon($contest, $winner['owner'], $winner['rank']);
            if ($promotionsModel) {
                $insertPromotionsData = $promotionsTable->insert($promotionsModel);
                if ($insertPromotionsData) {
                    $data['promoCode'] = $promotionsModel->promo_code;
                    if ($i == 1) {
                        $newMsExpDate = new \DateTime('+6 months', new \DateTimeZone('GMT'));
                        $upgradeMebership = $this->membershipService->upgradeToPlatinumMembership($winner['owner'], $newMsExpDate);
                        $this->sendEmail('Congratulations! You are the FIRST PLACE winner of our ' . $contest['name'], $winner['email'], 'contest_winner', $data);
                    } else {
                        $this->sendEmail('Congratulations! You are the RUNNER UP winner of our ' . $contest['name'], $winner['email'], 'contest_runnerup', $data);
                    }
                } else {
                    echo 'Error Occured While Inserting Contest Coupon Data Into Promotions Table Of The User:' . $winner['owner'] . "\n";
                }
            }

            $i++;
        }
    }

}
