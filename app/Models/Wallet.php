<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;
    protected $table = "wallets";
    protected $guarded = [];

    public function user(){
        return $this->hasOne(Customer::class,'customer_id','customer_id');
    }
}
