<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaystackPayment extends Model
{
    use HasFactory;
    protected $table = "paystack_payments";
    protected $guarded = [];
}
