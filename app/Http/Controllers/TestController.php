<?php

namespace App\Http\Controllers;

use App\services\Helper;
use Illuminate\Http\Request;
use App\services\TwiloService;
use App\services\PaystackService;
use App\services\SendGridService;
use App\Mail\WelcomeMessageWithPassword;
use Nette\Utils\Helpers;
use Symfony\Component\Console\Helper\Helper as HelperHelper;

class TestController extends Controller
{
    public function verifyEmail(Request $request)
    {
        $email = $request->email;
        if (Helper::valid_email($email)) {
            return response()->json(['status' => 'success', 'message' => 'Valid Email'], 200);
        }

        return response()->json(['status' => 'error', 'message' => 'Invalid Email'], 400);
    }

    public function validateAccountNumber(Request $request)
    {
        $accoutnumber = $request->accountnumber;
        $bankcode = $request->bankcode;
        return PaystackService::validateAccountNumber($accoutnumber, $bankcode);
    }

    public function createPaystackRecipient(Request $request)
    {
        $name = $request->name;
        $accoutnumber = $request->accountnumber;
        $bankcode = $request->bankcode;
        return PaystackService::createRecipient($name, $accoutnumber, $bankcode);
    }

    public function sendEmail(Request $request)
    {
        $subject = $request->subject;
        $to = $request->email;
        $name = $request->name;
        $html = "<h1>This is a test email</h1>";
        return SendGridService::sendEmail($subject, $to, $name, $html);
        // return SendGridService::sendEmail($to,$subject,$html);
    }

    public function sendSms(Request $request)
    {
        $to = $request->telephone;
        if(Helper::startsWith($to,"0")){
            $to = ltrim($to, '0');
        }
        if(Helper::startsWith($to,"+234")){
            $to = ltrim($to, '+234');
        }
        if(Helper::startsWith($to,"234")){
            $to = ltrim($to, '234');
        }
        $body = $request->body;
        return TwiloService::sendSMS($to, $body);
    }

    public function initializePayment(Request $request)
    {
        $amount = $request->amount;
        $email = $request->email;
        $reference_id = "RE".date('YmdHis');
        return PaystackService::initializePayment($amount,$email,$reference_id);
    }

    public static function sendEmaill(){
        $email = "abiodunflb20@gmail.com";
        $firstname = "Abiodun";
        try {
            $mail_data = [
                'firstname' => $firstname,
                'customer_id' => "CSTDDFDSFS",
                'unique' => "4334frrejkf"
            ];
            $html = (new WelcomeMessageWithPassword($mail_data))->render();
            SendGridService::sendEmail("Welcome To Tapol",$email,$firstname, $html);
        } catch (\Throwable $th) {
            return $th;
        }

        return "sent";
    }
}
