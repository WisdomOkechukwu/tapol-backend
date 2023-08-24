<?php

namespace App\Console\Commands;

use App\services\Helper;
use App\services\SendGridService;
use Illuminate\Console\Command;

class SendEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Email';

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
        $html = "<h1>Good Night Boss</h1>";
        return SendGridService::sendEmail("Hello","abiodunflb20@gmail.com","Abiodun",$html);
    }
}
