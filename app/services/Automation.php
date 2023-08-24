<?php
namespace App\services;

use App\Models\PaystackPayment;
use App\Models\Wallet;

class Automation
{
    public static function verifyPayment()
    {
        // this should run everyminute
        $payments = PaystackPayment::where('status', 0)->get();

        if ($payments->isEmpty()) {
            exit;
        }
        foreach ($payments as $payment) {
            $reference_id = $payment->reference_id;
            $customer_id = $payment->customer_id;
            $wallet = Wallet::where('customer_id', $customer_id)->first();
            if ($result = PaystackService::verifyPayment($reference_id)) {
                if ($result['status'] == true) {
                    if ($result['data']['gateway_response'] == "Successful") {
                        $authorization_code = $result['data']['authorization']['authorization_code'];
                        $last_four_digits = $result['data']['authorization']['last4'];
                        $payment->status = 1;
                        $wallet->balance += $payment->amount;
                        $wallet->authorization_code = $authorization_code;
                        $wallet->last_four_digits = $last_four_digits;
                        $payment->save();
                        $wallet->save();
                    }
                }
            }
        }

        return "done";
    }
}
