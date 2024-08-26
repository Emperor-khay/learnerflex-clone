<?php

namespace App\Enums;
enum VendorStatusEnum: string
{
    case DOWN = 'down';
    case PENDING = 'pending';
    case UP = 'up';
}
