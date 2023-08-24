<?php

namespace App\Console\Commands;

use App\Http\Controllers\SavingController;
use App\services\Helper;
use App\services\SendGridService;
use Illuminate\Console\Command;

class WithdrawToSavingBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'withdraw:to-savings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Withdraw To Savings Balance';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return SavingController::withdrawToSavingBalanceCron();
    }
}
