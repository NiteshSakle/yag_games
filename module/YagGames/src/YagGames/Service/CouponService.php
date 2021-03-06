<?php

namespace YagGames\Service;

class CouponService
{

    private $serviceManager;

    function __construct($serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    public function generateWinnersCoupon($contestInfo, $memId, $winnerPosition, $couponType)
    {
        try {
            $couponOptions = array();

            // Upto 16x20 Photographic Print,Gallery Plexi Mounted Photo - CouponType:1 (The Final Two Prize)
            $couponOptions[1] = array(
                'product_type' => 'Photographic Print,Gallery Plexi Mounted Photo',
                'dimensions' => '8 x 10,8 x 24,9 x 12,10 x 8,12 x 9,12 x 12,12 x 18,16 x 16,16 x 20,18 x 12,20 x 16,24 x 8'
            );

            // Upto 12x18 Photographic Print - CouponType:2 (The Core Four Prize)
            $couponOptions[2] = array(
                'product_type' => 'Photographic Print',
                'dimensions' => '8 x 10,9 x 12,10 x 8,12 x 9,12 x 12,12 x 18,18 x 12'
            );
            
            // All Prints on All sizes (Dollar off coupon $250) - CouponType:3 (CHAMPIONSHIP Prize)
            $couponOptions[3] = array(
                'product_type' => 'Photographic Print,Gallery Plexi Mounted Photo,Giclee Fine Art Print,Frames Photo Print,Giclee Frame Print,Stretched Canvas Frame',
                'dimensions' => '8 x 10,8 x 24,9 x 12,10 x 8,12 x 9,12 x 12,12 x 18,12 x 36,16 x 16,16 x 20,16 x 48,18 x 12,18 x 24,20 x 16,20 x 30,24 x 8,24 x 18,24 x 24,24 x 30,30 x 20,30 x 24,30 x 30,30 x 40,36 x 12,40 x 30,40 x 40,40 x 50,40 x 60,48 x 16,48 x 48,48 x 60,48 x 72,50 x 40,60 x 40,60 x 48,72 x 48'
            );
            
            $promotionsModel = new \YagGames\Model\Promotions();
            $promotionsModel->upromo_id = $this->createUnique2();
            $promotionsModel->user_id = $memId;
            $promotionsModel->target_product_type = '';
            $promotionsModel->target_dimensions = '';
            $promotionsModel->coupon_type = '0'; // String Enum in Table Schema
            $promotionsModel->onflygeneration = '0'; // String Enum in Table Schema
            $promotionsModel->sortorder = 0;
            $promotionsModel->active = 1;
            $promotionsModel->notes = '';
            $promotionsModel->everyone = 0;
            $promotionsModel->oneuse = 1;
            $promotionsModel->autoapply = 0;
            $promotionsModel->minpurchase = 0.0000;
            $promotionsModel->quantity = 1;
            $promotionsModel->promotype = ($couponType == 3 ) ? 'prioff' : 'peroff';
            $promotionsModel->peroff = ($couponType == 3 ) ? 0 : 100;
            $promotionsModel->prioff = ($couponType == 3 ) ? 250 : 0;
            $promotionsModel->bulkbuy = 0;
            $promotionsModel->bulktype = '';
            $promotionsModel->bulkfree = 0;
            $promotionsModel->homepage = 0;
            $promotionsModel->cartpage = 0;
            $promotionsModel->promopage = 0;
            $promotionsModel->deleted = 0;
            $promotionsModel->name_dutch = '';
            $promotionsModel->name_french = '';
            $promotionsModel->name_german = '';
            $promotionsModel->name_spanish = '';
            $promotionsModel->name_english = '';
            $promotionsModel->description_dutch = '';
            $promotionsModel->description_french = '';
            $promotionsModel->description_german = '';
            $promotionsModel->description_spanish = '';
            $promotionsModel->description_english = '';
            $promotionsModel->max_discount_amount = 0;
            $promotionsModel->coupon_validity_period_start = date('Y-m-d');
            $promotionsModel->coupon_validity_period_end = '0000-00-00';
            $promotionsModel->shipping = '1'; // String Enum in Table Schema
            $promotionsModel->shipping_type = 'FEDEX_GROUND,FEDEX_2_DAY,PRIORITY_OVERNIGHT,INTERNATIONAL_ECONOMY';
            $promotionsModel->membership_type = 0;
            $promotionsModel->membership_period = '';
            $promotionsModel->user_type = '';

            if ($winnerPosition == 1) {
                //Winner
                $promotionsModel->name = $contestInfo['name'] . ' ' . date('Y') . ' Winner';
                $promotionsModel->description = '1st Place Winner of ' . $contestInfo['name'] . ' ' . date('Y');
            } else {
                //Runner Up
                $promotionsModel->name = $contestInfo['name'] . ' ' . date('Y') . ' Runner Up';
                $promotionsModel->description = $contestInfo['name'] . ' ' . date('Y') . ' Runner Up';
            }

            $promotionsModel->product_type = $couponOptions[$couponType]['product_type'];
            $promotionsModel->dimensions = $couponOptions[$couponType]['dimensions'];

            //Generate Unique Coupon Code
            $promoCode = $this->createUniqueCouponCode();
            $promotionsTable = $this->serviceManager->get('YagGames\Model\PromotionsTable');
            while ($promotionsTable->checkPromoCodeExist($promoCode)) {
                $promoCode = $this->createUniqueCouponCode();
            }
            $promotionsModel->promo_code = $promoCode;

            return $promotionsModel;
        } catch (Exception $ex) {
            echo "Something went wrong in creation of coupon\n";
        }

        return FALSE;
    }

    private function createUnique2()
    {
        return strtoupper(md5(microtime()));
    }

    private function createUniqueCouponCode()
    {
        return strtoupper($this->fullClean(substr(md5(microtime()), 0, 6)));
    }

    private function fullClean($input)
    {
        $clean = str_replace(" ", "", $input);
        $clean = html_entity_decode($clean);
        $clean = preg_replace("/[^A-Za-z0-9_-]/", "", $clean);
        return $clean;
    }

}
