<?php

namespace App\Http\Controllers;

use App\Http\Requests\wallet\ConfirmDepositRequest;
use App\Http\Requests\wallet\CreateWalletRequest;
use App\Http\Requests\wallet\EditWalletRequest;
use App\Http\Requests\wallet\InitializeDepositRequest;
use App\Http\Requests\wallet\InitiateWithdrawalRequest;
use App\Http\Requests\wallet\VerifyWithdrawalRequest;
use App\Models\Customer;
use App\Models\PaystackPayment;
use App\Models\Transaction;
use App\Models\TransferRecipient;
use App\Models\Wallet;
use App\services\Helper;
use App\services\PaystackService;
use App\services\WalletService;

class WalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:customers', ['except' => []]);
    }

    public function createWallet(CreateWalletRequest $request)
    {
        if (Wallet::where('customer_id', $request->customer_id)->first()) {
            return response()->json(['status' => 'error', 'message' => 'Customer Has A Wallet Already'], 400);
        }
        Helper::updateUpdatedAtColumn();

        if (!$customer = Customer::where('customer_id', $request->customer_id)->first()) {
            return response()->json(['status' => 'error', 'message' => 'Customer Not Found'], 400);
        }

        $fullname = "$customer->firstname $customer->lastname";

        $bankdata = PaystackService::validateAccountNumber(trim($request->accountnumber), trim($request->bankcode));
        if ($bankdata) {
            if ($bankdata["status"] != "success") {
                return response()->json(['status' => 'error', 'message' => 'Invalid Bank Details'], 400);
            }
        } else {
            return response()->json(['status' => 'error', 'message' => 'Invalid Bank Details'], 400);
        }

        if (!WalletService::createWallet($request->customer_id, $fullname, $request->accountnumber, $request->bankcode)) {
            return response()->json(['status' => 'error', 'message' => 'Error Creating Wallet'], 400);
        }

        return response()->json(['status' => 'success', 'message' => 'Wallet Created Successfully'], 200);
    }

    public function editWallet(EditWalletRequest $request)
    {
        $customer_id = auth('customers')->user()->customer_id;
        Helper::updateUpdatedAtColumn();
        if (!$wallet = Wallet::select('id', 'accountnumber', 'bankcode')->where('customer_id', $customer_id)->first()) {
            return response()->json(['status' => 'error', 'message' => 'Wallet Not Found'], 400);
        }

        $bankdata = PaystackService::validateAccountNumber(trim($request->accountnumber), trim($request->bankcode));
        if ($bankdata) {
            if ($bankdata["status"] != "success") {
                return response()->json(['status' => 'error', 'message' => 'Invalid Bank Details'], 400);
            }
        } else {
            return response()->json(['status' => 'error', 'message' => 'Invalid Bank Details'], 400);
        }

        $wallet->accountnumber = $request->accountnumber;
        $wallet->bankcode = $request->bankcode;
        $wallet->save();

        return response()->json(['status' => 'success', 'message' => 'Wallet Edited Successfully'], 200);
    }

    public function initializeDeposit(InitializeDepositRequest $request)
    {
        $customer = auth('customers')->user();
        Helper::updateUpdatedAtColumn();
        $customer_id = $customer->customer_id;
        $reference_id = "RE" . Date('YmdHis');

        $amount = 0.01 * $request->amount;
        $amount = $amount + $request->amount;

        $payment = new PaystackPayment();
        $payment->customer_id = $customer_id;
        $payment->amount = $request->amount;
        $payment->real_amount_debited = $amount;
        $payment->reference_id = $reference_id;
        $payment->type = "Deposit";
        $payment->save();

        return response()->json(['status' => 'success', 'message' => 'Successful', 'reference' => $reference_id, 'amount' => $amount], 200);
    }

    public function confirmDeposit(ConfirmDepositRequest $request)
    {
        $customer_id = $request->customer_id;
        $reference_id = $request->reference_id;
        $payment = PaystackPayment::where('status', 0)->where('customer_id', $customer_id)->where('reference_id', $reference_id)->first();
        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment Not Found'], 400);
        }
        $wallet = Wallet::where('customer_id', $customer_id)->first();
        if ($result = PaystackService::verifyPayment($reference_id)) {
            if ($result['status'] == true) {

                $authorization_code = $result['data']['authorization']['authorization_code'];
                $last_four_digits = $result['data']['authorization']['last4'];
                $payment->status = 1;

                $wallet->balance += $payment->amount;
                $wallet->authorization_code = $authorization_code;
                $wallet->last_four_digits = $last_four_digits;
                $payment->save();
                $wallet->save();
                $transaction = new Transaction();
                $transaction->customer_id = $customer_id;
                $transaction->message = "Made a deposit of N$payment->amount";
                $transaction->type = "wallet_topup";
                $transaction->amount = $payment->amount;
                $transaction->save();
                return response()->json(['status' => 'success', 'message' => 'Deposit Successful'], 200);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Deposit Not Successful'], 400);
            }
        }
        return response()->json(['status' => 'success', 'message' => 'Unknown Error'], 400);
    }

    public function Initiatewithdraw(InitiateWithdrawalRequest $request)
    {
        $customer = auth('customers')->user();
        Helper::updateUpdatedAtColumn();
        $customer_id = $customer->customer_id;
        $cus = Customer::where('customer_id', $customer_id)->first();
        $bankcode = $request->bankcode;
        $accountnumber = $request->accountnumber;
        $note = $request->note;
        $reference_id = "wdwl" . Date('YmdHis');

        $bankdata = PaystackService::validateAccountNumber(trim($request->accountnumber), trim($request->bankcode));
        if (!$bankdata) {
            return response()->json(['status' => 'error', 'message' => 'Unable to verify bank details'], 400);
        }
        $verified_name = $bankdata['data']['account_name'];
        if (!$existing_recipient = TransferRecipient::where('accountnumber', $accountnumber)->first()) {
            if (!$recipient = PaystackService::createRecipient($verified_name, $accountnumber, $bankcode)) {
                return response()->json(['status' => 'error', 'message' => 'Unable to create recipient'], 400);
            }
            $recipient_id = $recipient['data']['recipient_code'];
            $field = [
                'customer_id' => $customer_id,
                'recipient_code' => $recipient_id,
                'fullname' => $verified_name,
                'accountnumber' => $accountnumber,
                'bankcode' => $bankcode,
                'reference_id' => $reference_id,
            ];

            TransferRecipient::create($field);
        } else {
            $recipient_id = $existing_recipient->recipient_code;
            $existing_recipient->reference_id = $reference_id;
            $existing_recipient->save();
        }

        $telephone = $request->telephone;
        if (!$telephone) {
            $telephone = $customer->telephone;
        }
        if (!OtpController::sendOtp($telephone)) {
            return response()->json(['status' => 'error', 'message' => 'Unable to Send Otp'], 400);
        }
        $cus->telephone = $telephone;
        $cus->save();
        $payment = new PaystackPayment();
        $payment->customer_id = $customer_id;
        $payment->amount = $request->amount;
        $payment->reference_id = $reference_id;
        $payment->type = "Withdrawal";
        $payment->note = $note;
        $payment->save();

        return response()->json(['status' => 'success', 'message' => 'Withdrawal Initiated Successfully', 'reference_id' => $reference_id, 'recipient_id' => $recipient_id, 'recipient_name' => $verified_name, 'reason' => $note], 200);
    }

    public function verifyWithdrawal(VerifyWithdrawalRequest $request)
    {
        $customer_id = $request->customer_id;
        $reference_id = $request->reference_id;
        $fee = 50;
        $payment = PaystackPayment::where('status', 0)->where('customer_id', $customer_id)->where('reference_id', $reference_id)->first();
        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment Not Found'], 400);
        }
        $amount = $payment->amount + $fee;

        if (!$customer = Customer::where('customer_id', $customer_id)->first()) {
            return response()->json(['status' => 'error', 'message' => 'Customer Not Found'], 400);
        }

        if (!OtpController::verifyOtp($request->otp, $customer->telephone)) {
            $otp = $payment->otp;
            if (!$otp) {
                return response()->json(['status' => 'error', 'message' => 'Otp verification failed'], 400);
            }
        }

        $payment->otp = $request->otp;
        $payment->save();

        if (!$recipient = TransferRecipient::where('reference_id', $reference_id)->first()) {
            return response()->json(['status' => 'error', 'message' => 'Recipient Not Found'], 400);
        }

        if (!PaystackService::initiateTransfer($payment->amount, $recipient->recipient_code, $payment->note, $reference_id)) {
            return response()->json(['status' => 'error', 'message' => 'Unable to Initiate Withdrawal'], 400);
        }

        if (!PaystackService::verifyTransfer($reference_id)) {
            return response()->json(['status' => 'error', 'message' => 'Withdrawal not successful'], 400);
        }
        $wallet = Wallet::where('customer_id', $customer_id)->first();
        $payment->status = 1;
        $wallet->balance -= $amount;
        $payment->save();
        $wallet->save();

        $transaction = new Transaction();
        $transaction->customer_id = $customer_id;
        $transaction->message = "Made a withdrawal of N$payment->amount to $recipient->fullname";
        $transaction->type = "wallet_withdrawal";
        $transaction->amount = $payment->amount;
        $transaction->save();
        return response()->json(['status' => 'success', 'message' => 'Withdrawal Successful'], 200);
    }

}
