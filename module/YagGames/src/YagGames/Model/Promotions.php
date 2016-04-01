<?php

namespace YagGames\Model;

class Promotions extends BaseModel
{
    public $promo_id;
    public $upromo_id;
    public $name;
    public $user_id;
    public $product_type;
    public $dimensions;
    public $target_product_type;
    public $target_dimensions;
    public $coupon_type;
    public $onflygeneration;
    public $description;
    public $sortorder;
    public $active;
    public $notes;
    public $everyone;
    public $promo_code;
    public $oneuse;
    public $autoapply;
    public $minpurchase;
    public $quantity;
    public $promotype;
    public $peroff;
    public $prioff;
    public $bulkbuy;
    public $bulktype;
    public $bulkfree;
    public $homepage;
    public $cartpage;
    public $promopage;
    public $deleted;
    public $name_dutch;
    public $name_french;
    public $name_german;
    public $name_spanish;
    public $name_english;
    public $description_dutch;
    public $description_french;
    public $description_german;
    public $description_spanish;
    public $description_english;
    public $max_discount_amount;
    public $coupon_validity_period_start;
    public $coupon_validity_period_end;
    public $shipping;
    public $shipping_type;
    public $membership_type;
    public $membership_period;
    public $user_type;
}
