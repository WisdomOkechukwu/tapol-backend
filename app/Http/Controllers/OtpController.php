<?php

namespace App\Http\Controllers;

use App\services\TwiloService;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public static function sendOtp($telephone)
    {
        if (!TwiloService::sendToken($telephone)) {
            return false;
        }
        // return response()->json(['status' => 'success', 'message' => 'Otp Sent Successfully'], 200);
        return true;

    }

    public static function verifyOtp($otp,$telephone)
    {
        if (!TwiloService::verifyToken($telephone, $otp)) {
            return false;
        }
        return true;
    }
}
