<?php

namespace App\Enums;

enum TransactionDescription : string
{
    case SIGNUP_FEE = 'signup_fee';
    case MARKETPLACE_UNLOCK = 'marketplace_unlock';
    case PRODUCT_SALE = 'product_sale';
    case IS_ONBOARDED = 'onboarded';
    case POST_ONBOARD = 'post_onboard';
}
