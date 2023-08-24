<?php

namespace App\Console\Commands;

use App\Http\Controllers\SavingController;
use App\services\Helper;
use App\services\SendGridService;
use Illuminate\Console\Command;

class WithdrawSavingToWalletOnWithdrawalDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'withdraw:to-wallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Withdraw To Wallet On Withdrawal Day';

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
        return SavingController::WithdrawToWalletCron();
    }
}
