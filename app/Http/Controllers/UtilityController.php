<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\services\PaystackService;

class UtilityController extends Controller
{
    public function getBanks()
    {
        return PaystackService::getBanks();
    }
}
