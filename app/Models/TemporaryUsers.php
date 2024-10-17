<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryUsers extends Model
{
    use HasFactory;
    protected $table = 'temporary_users';
    protected $fillable = ['name', 'email', 'phone', 'password', 'aff_id', 'order_id'];
}
