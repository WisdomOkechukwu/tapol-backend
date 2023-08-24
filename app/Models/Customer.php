<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends User implements JWTSubject
{
    use HasFactory;
    protected $table = "customers";
    protected $guarded = [];

    protected $hidden = ['pin'];
    protected $appends = ['profile_picture_link'];

    public function wallet()
    {
        return $this->hasOne(Wallet::class,'customer_id','customer_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class,'customer_id','customer_id')->latest();
    }

    public function loans()
    {
        return $this->hasMany(Loan::class,'customer_id','customer_id')->latest();
    }

    public function waitingLoans()
    {
        return $this->hasMany(Loan::class,'customer_id','customer_id')->where('status',0)->latest();
    }

    public function activeLoans()
    {
        return $this->hasMany(Loan::class,'customer_id','customer_id')->where('status',1)->latest();
    }

    public function getProfilePictureLinkAttribute()
    {
        if ($this->profile_picture == null) {
            return null;
        } else {
            $document = $this->profile_picture;
            $link = asset("storage/documents/profile_pictures/".$document);
            return $link;
        }
    }
}
