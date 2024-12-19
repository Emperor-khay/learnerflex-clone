<?php

namespace App\Enums;

enum TransactionDescription : string
{
    case SIGNUP_FEE = 'signup_fee';
    case MARKETPLACE_UNLOCK = 'marketplace_unlock';
    case PRODUCT_SALE = 'product_sale';
}
