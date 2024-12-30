<?php

namespace App\Enums;

enum VendorStatusEnum: string
{
    case Pending = 'pending';
    case Inactive = 'inactive';
    case Active = 'active';
}