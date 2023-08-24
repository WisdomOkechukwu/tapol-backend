<?php
namespace App\services;

use App\Models\PaystackPayment;
use App\Models\Transaction;
use App\Models\Wallet;

class WalletService
{

    public static function createWallet($customer_id, $name, $accountnumber, $bankcode)
    {
        if (Wallet::where('customer_id', $customer_id)->whereNotNull('recipient_id')->first()) {
            // return response()->json(['status'=>'error','message'=>'Recipient Exist Already'],400);
            return false;
        }
        if ($recipient = PaystackService::createRecipient($name, $accountnumber, $bankcode)) {
            if ($recipient['status'] == true) {
                $recipient_id = $recipient['data']['recipient_code'];

                $field = [
                    'customer_id' => $customer_id,
                    'recipient_id' => $recipient_id,
                    'accountnumber' => $accountnumber,
                    'bankcode' => $bankcode,
                ];

                Wallet::create($field);
                return true;
            }
        }
        return false;
    }

    public static function getBalanceAmount($customer_id)
    {
        if (!$wallet = Wallet::where('customer_id', $customer_id)->first()) {
            // return response()->json(['status'=>'error','message'=>'Wallet Not Found'],400);
            return Helper::getResponseMessage(400, "Wallet Not Found", "error");
        }

        return $wallet->balance;
    }

    public static function InitiateDeposit($customer_id, $amount, $email, $reference_id)
    {
        $payment = new PaystackPayment();
        $payment->customer_id = $customer_id;
        $payment->amount = $amount;
        $payment->reference_id = $reference_id;
        $payment->type = "Deposit";
        $payment->save();
        return PaystackService::initializePayment($amount, $email, $reference_id);
    }

    public static function initiateWithdrawal($amount, $customer_id, $recipient)
    {
        if (!$wallet = Wallet::where('customer_id', $customer_id)->first()) {
            return Helper::getResponseMessage(400, "Wallet Not Found", "error");
        }

        if ($wallet->balance < $amount) {
            return ['status' => 'error', 'message' => 'Insufficient Wallet Balance'];
        }
        $reference_id = "wdwl" . Date('YmdHis');

        if (!$result = PaystackService::initiateTransfer($amount, $recipient, "Wallet Withdrawl", $reference_id)) {
            return ['status' => 'error', 'message' => 'Unable To Initiate Transfer'];
        }
        $payment = new PaystackPayment();
        $payment->customer_id = $customer_id;
        $payment->amount = $amount;
        $payment->reference_id = $reference_id;
        $payment->type = "Withdrawal";
        $payment->save();
        $authorization_code = $result['data']['authorization']['authorization_code'] ?? null;
        $last4 = $result['data']['authorization']['last4'] ?? null;

        // return response()->json(['status' => 'success', 'message' => 'Withdrawal Initiated Successfully', 'reference_id' => $reference_id], 200);

        return ['status' => 'success', 'reference_id' => $reference_id, 'authorization_code' => $authorization_code,'four_digits'=> $last4 ];
    }

    public static function withdraw($request, $customer_id, $recipient_id)
    {
        $result = self::initiateWithdrawal($request->amount, $customer_id, $recipient_id);
        if ($result) {
            if ($result['status'] == "error") {
                return response()->json(['status' => 'error', 'message' => $result['message'] ?? "Unable To Initialize Withdrawal"], 400);
            }
            $reference_id = $result['reference_id'];
            $verify = PaystackService::verifyTransfer($reference_id);
            if (!$verify) {
                return response()->json(['status' => 'error', 'message' => 'Withdrawal Verification Failed'], 400);
            }
            $payment = PaystackPayment::where('status', 0)->where('customer_id', $customer_id)->first();
            $wallet = Wallet::where('customer_id', $customer_id)->first();
            $authorization_code = $result['authorization_code'];
            $last_four_digits = $result['four_digits'];
            $payment->status = 1;
            $wallet->balance -= $payment->amount;
            $wallet->authorization_code = $authorization_code;
            $wallet->last_four_digits = $last_four_digits;
            $payment->save();
            $wallet->save();
            $transaction = new Transaction();
            $transaction->customer_id = $customer_id;
            $transaction->message = "Made a withdrawal of N$payment->amount";
            $transaction->type = "wallet_withdrawal";
            $transaction->amount = $payment->amount;
            $transaction->save();
            return response()->json(['status' => 'success', 'message' => 'Withdrawal Successful'], 200);
        }

    }

}
