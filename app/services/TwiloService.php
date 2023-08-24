<?php
namespace App\services;

use Twilio\Rest\Client;

class TwiloService
{

    // public static function sendSMS2($to, $body)
    // {
    //     $url = "https://api.twilio.com/2010-04-01/Accounts/AC0f860a5743afdbc3f3d19167cfa5b8f6/Messages.json";

    //     $field = [
    //         'body' => $body,
    //         'from' => "+16693221347",
    //         'to' => $to,
    //     ];

    //     $data = Http::withHeaders([
    //         'Accept' => 'application/json',
    //         'Authorization' => 'Basic QUMwZjg2MGE1NzQzYWZkYmMzZjNkMTkxNjdjZmE1YjhmNjpkNTRmNzQzOTY0MjY5NmZhMDY0MjBkYjEwOWIwYmVjNQ==',
    //         'Content-Type' => 'application/json',
    //     ])->post($url, $field)->json();
    //     return $data;
    // }

    public static function sendSMS($to, $body)
    {
        $sid = 'AC0f860a5743afdbc3f3d19167cfa5b8f6';
        $token = 'd54f7439642696fa06420db109b0bec5';
        $client = new Client($sid, $token);
        $message = $client->messages->create(
            // the number you'd like to send the message to
            "+234" . $to,
            [
                // A Twilio phone number you purchased at twilio.com/console
                'from' => '+16693221347',
                // the body of the text message you'd like to send
                'body' => $body,
            ]
        );

        print($message->sid);
    }

    public static function createVerificationService()
    {
        $sid = 'AC0f860a5743afdbc3f3d19167cfa5b8f6';
        $token = 'd54f7439642696fa06420db109b0bec5';
        $client = new Client($sid, $token);
        $service = $client->verify->v2->services
            ->create("TAPOL WITHRAWAL OTP");
        return $service;
    }

    public static function sendToken($to)
    {
        $sid = 'AC0f860a5743afdbc3f3d19167cfa5b8f6';
        $token = 'd54f7439642696fa06420db109b0bec5';
        $client = new Client($sid, $token);

        try { $verification = $client->verify->v2->services("VA8f75922861be720c6c24d956b3961934")
                ->verifications
                ->create("+234" . $to, "sms");

        } catch (\Throwable $th) {
            return false;
        }

        if ($verification) {
            if ($verification->status == "pending") {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public static function verifyToken($to, $code)
    {
        $sid = 'AC0f860a5743afdbc3f3d19167cfa5b8f6';
        $token = 'd54f7439642696fa06420db109b0bec5';
        $client = new Client($sid, $token);

        try {
            $verification_check = $client->verify->v2->services("VA8f75922861be720c6c24d956b3961934")
                ->verificationChecks
                ->create([
                    "to" => "+234" . $to,
                    "code" => $code,
                ]
                );
        } catch (\Throwable $th) {
            return false;
        }

        if ($verification_check) {
            if ($verification_check->status == "approved") {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
