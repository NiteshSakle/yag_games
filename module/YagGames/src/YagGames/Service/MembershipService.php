<?php

namespace YagGames\Service;

class MembershipService
{

    private $serviceManager;

    function __construct($serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    public function upgradeToPlatinumMembership($memId, $newExpDateObj)
    {
        try {
            if (!($newExpDateObj instanceof \DateTime)) {
                throw new Exception('Invalid Expiry Date Object Passed!');
            }

            $memberTable = $this->serviceManager->get('YagGames\Model\MembersTable');
            $member = $memberTable->fetchRecord($memId);
            if ($member) {
                $currentMsEndDate = \DateTime::createFromFormat('Y-m-d H:i:s', $member->ms_end_date);
                
                // If Current Membership Expiry Date Not Valid Date Then Set To Current DateTime
                if (!($currentMsEndDate && $currentMsEndDate->format('Y-m-d H:i:s') == $member->ms_end_date)) {
                    $currentMsEndDate = new \DateTime();
                    $currentMsEndDate->setTimezone(new \DateTimeZone('GMT'));                    
                }                
                
                if ($member->membership == 4 && $currentMsEndDate > $newExpDateObj) {
                    // Don't Upgrade If Current Membership Is Platinum With Current Exp Date Greater Than Passed Exp Date.
                    echo "Membership Upgradation For The User $memId Is Not Done, Has Membership Is Already A Plantium With Future Exp Date.\n";
                    
                    return TRUE;
                } else {
                    $memberUpdateData = array();                    
                    $memberUpdateData['membership'] = 4;
                    $memberUpdateData['ms_end_date'] = $newExpDateObj->format('Y-m-d H:i:s');
                    $memberUpdateData['recurring_payment_failed'] = 0;
                    $memberUpdateData['processing_date'] = '0000-00-00';
                    $memberUpdateData['is_membership_downgrade_id'] = 0;
                    $memberUpdateData['membership_downgrade_date'] = '0000-00-00';
                    //Update Membership
                    $membersTable = $this->serviceManager->get('YagGames\Model\MembersTable');                   
                    if ($membersTable->updateSpecificFields($memId, $memberUpdateData)) {
                        return TRUE;
                    } else {
                        echo "Membership Upgradation Failed For The User: $memId\n";
                    }
                    
                }
            }

            return FALSE;
        } catch (Exception $ex) {
            echo "Something Went Wrong While Upgrading User $memId To Platinum Membership\n";
        }
    }

}
