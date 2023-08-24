<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;
    protected $table = "loans";
    protected $guarded = [];

    protected $appends = ['days_left'];

    public function getDaysLeftAttribute()
    {
        if($this->loan_start_date AND $this->loan_end_date):
            if($this->loan_end_date >= now()):
                $options = [
                    'join' => ', ',
                    'parts' => 2,
                    'syntax' => CarbonInterface::DIFF_ABSOLUTE,
                ];

                return Carbon::parse($this->loan_end_date)->diffForHumans(Carbon::parse($this->loan_start_date), $options);
            endif;
            return 'loan expired';
        endif;

        return 'loan pending';
    }
}
