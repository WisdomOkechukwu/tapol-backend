<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\Saving;
use App\Models\Wallet;
use App\Models\Customer;
use App\services\Helper;
use App\Mail\NewSavingsMail;
use App\services\SendGridService;
use App\Mail\CompletedSavingsMail;
use App\Http\Requests\savings\ListSavingsRequest;
use App\Http\Requests\savings\TopUpSavingRequest;
use App\Http\Requests\savings\CreateSavingsRequest;
use App\Http\Requests\savings\InitiateSavingsRequest;
use App\Http\Requests\savings\WithdrawSavingsRequest;

class SavingController extends Controller
{
    // 0 -active saving   1-Completed Savings
    public function __construct()
    {
        $this->middleware('auth:customers', ['except' => []]);
    }
    public function initiateSavings(InitiateSavingsRequest $request)
    {
        //1-3 months 1.58% 3-6 months 1.68% 6-9 1.78% 9-12 1.88%
        Helper::updateUpdatedAtColumn();
        $amount_to_save = $request->amount_to_save;
        $saving_title = $request->saving_title;
        if ($amount_to_save < 100000) {
            return response()->json(['status' => 'error', 'message' => 'Amount To Save Must Be Greater Than 99,999'], 400);
        }
        $start_date = $request->start_date;
        // $no_of_frequency = (int) $request->tenor;
        $maturity_date = $request->maturity_date;
        // $maturity_date = date('Y-m-d', strtotime("+$no_of_frequency months", strtotime($start_date)));

        $datetime1 = new DateTime($start_date);
        $datetime2 = new DateTime($maturity_date);
        $interval = $datetime1->diff($datetime2);
        $diff_days = $interval->format('%a');

        if ($diff_days < 30) {
            return response()->json(['status' => 'error', 'message' => 'No of days to maturity date Must be greater than 30 days'], 400);
        }

        // 3 months -> end of 5 months
        if ($diff_days <= 91) {
            $interest_per_day = ((1.58 / 100) / 3) / 30;
        }

        if ($diff_days > 91 && $diff_days <= 181) {
            $interest_per_day = ((1.68 / 100) / 3) / 30;
        }

        if ($diff_days > 181 && $diff_days <= 271) {
            $interest_per_day = ((1.78 / 100) / 3) / 30;
        }

        if ($diff_days > 271 && $diff_days <= 361) {
            $interest_per_day = ((1.88 / 100) / 3) / 30;
        }

        if ($diff_days > 361) {
            $interest_per_day = ((1.88 / 100) / 3) / 30;
        }

        $interest_amount_daily = $amount_to_save * $interest_per_day;
        $interest_due = $interest_amount_daily * $diff_days;

        $total_maturity_amount = $amount_to_save + $interest_due;
        $total_interest_rate = $total_maturity_amount - $amount_to_save;
        $total_interest_rate = ($total_interest_rate / $amount_to_save) * 100;
        $total_interest_rate = number_format($total_interest_rate, 2);

        return response()->json(['status' => 'success','saving_title' => $saving_title, 'amount_to_save' => $amount_to_save, 'start_date' => $request->start_date, 'maturity_date' => $maturity_date, 'interest_due' => $interest_due, 'total_maturity_amount' => $total_maturity_amount, 'total_interest_rate' => "$total_interest_rate%"], 200);
    }

    public function createSavings(CreateSavingsRequest $request)
    {
        $customer = auth('customers')->user();
        Helper::updateUpdatedAtColumn();
        $customer_id = $customer->customer_id;

        if (!$wallet = Wallet::select('id', 'balance')->where('customer_id', $customer_id)->first()) {
            return response()->json(['status' => 'error', 'message' => 'Wallet Not Found'], 400);
        }
        $balance = $wallet->balance;
        if ($balance < $request->amount_to_save) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient Balance'], 400);
        }

        $wallet->balance -= $request->amount_to_save;
        $wallet->save();

        $saving_id = "save" . date('YmdHis');

        $field = [
            'saving_id' => $saving_id,
            'customer_id' => $customer_id,
            'amount_to_save' => $request->amount_to_save,
            'start_date' => $request->start_date,
            'maturity_date' => $request->maturity_date,
            'total_maturity_amount' => $request->total_maturity_amount,
            'interest_due' => $request->interest_due,
            'saving_title' => $request->saving_title
        ];

        Saving::create($field);

        try {
            $mail_data = [
                'fullname' => $customer->firstname . " " . $customer->lastname,
                'saving_id' => $saving_id,
                'amount_to_save' => $request->amount_to_save,
                'start_date' => $request->start_date,
                'maturity_date' => $request->maturity_date,
                'total_maturity_amount' => $request->total_maturity_amount,
                'interest_due' => $request->interest_due,
                'saving_title' => $request->saving_title
            ];

            $html = (new NewSavingsMail($mail_data))->render();
            SendGridService::sendEmail("Savings Details", $customer->email, $customer->firstname, $html);

        } catch (\Throwable $th) {
        }

        Helper::storeTransaction($customer_id, "Started a savings with saving id $saving_id",$request->amount_to_save,'savings');
        return response()->json(['status' => 'success', 'message' => 'Savings Created Successfully'], 200);
    }

    public function listSavings(ListSavingsRequest $request)
    {
        $user = auth('customers')->user();
        Helper::updateUpdatedAtColumn();
        $savings = Saving::query();

        if ($request->search_text) {
            $search_text = $request->search_text;
            $savings = $savings->where('saving_id', $search_text);
        }

        if($request->start_date && $request->end_date){
            $start = "$request->start_date 00:00:00";
            $end = "$request->end_date 23:59:59";
            $savings = $savings->whereBetween('created_at',[$start,$end]);
        }

        $savings = $savings->orderBy('id', 'DESC')->where('status', $request->status)->where('customer_id', $user->customer_id)->paginate($request->page_size);
        return response()->json(['status' => 'success', 'savings' => $savings], 200);
    }

    public function topUpSaving(TopUpSavingRequest $request)
    {
        $customer = auth('customers')->user();
        Helper::updateUpdatedAtColumn();
        $customer_id = $customer->customer_id;

        if (!$wallet = Wallet::select('id', 'balance')->where('customer_id', $customer_id)->first()) {
            return response()->json(['status' => 'error', 'message' => 'Wallet Not Found'], 400);
        }
        $balance = $wallet->balance;
        if ($balance < $request->amount) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient Balance'], 400);
        }

        if (!$saving = Saving::where('saving_id', $request->saving_id)->first()) {
            return response()->json(['status' => 'error', 'message' => 'Saving Not Found'], 400);
        }

        $total_amount = $saving->amount_to_save;
        if($request->amount){
            $amount = $request->amount;
            $wallet->balance -= $amount;
            $total_amount = $saving->amount_to_save + $amount;
            $saving->amount_to_save = $total_amount;
            $wallet->save();
        }


        $maturity_date = $saving->maturity_date;

        if ($request->maturity_date) {
            $maturity_date = $request->maturity_date;
        }


        $interest_due = Self::calculateSavingInterestAmount($total_amount, $saving->start_date, $maturity_date);
        $total_maturity_amount = $total_amount + $interest_due;
        $saving->total_maturity_amount = $total_maturity_amount;
        $saving->interest_due = $interest_due;
        $saving->maturity_date = $maturity_date;
        $saving->save();
        Helper::storeTransaction($customer_id, "Top Up savings with saving id $request->saving_id",$request->amount,'savings');
        return response()->json(['status' => 'success', 'message' => 'Saving Top Up Successful'], 200);
    }

    public static function calculateSavingInterestAmount($total_amount, $start_date, $maturity_date)
    {
        $datetime1 = new DateTime($start_date);
        $datetime2 = new DateTime($maturity_date);
        $interval = $datetime1->diff($datetime2);
        $diff_days = $interval->format('%a');

        if ($diff_days <= 91) {
            $interest_per_day = ((1.58 / 100) / 3) / 30;
        }

        if ($diff_days > 91 && $diff_days <= 181) {
            $interest_per_day = ((1.68 / 100) / 3) / 30;
        }

        if ($diff_days > 181 && $diff_days <= 271) {
            $interest_per_day = ((1.78 / 100) / 3) / 30;
        }

        if ($diff_days > 271 && $diff_days <= 361) {
            $interest_per_day = ((1.88 / 100) / 3) / 30;
        }

        if ($diff_days > 361) {
            $interest_per_day = ((1.88 / 100) / 3) / 30;
        }

        $interest_amount_daily = $total_amount * $interest_per_day;
        return $interest_due = $interest_amount_daily * $diff_days;
    }

    public function withdrawSavings(WithdrawSavingsRequest $request)
    {
        $customer = auth('customers')->user();
        $customer_id = $customer->customer_id;
        Helper::updateUpdatedAtColumn();

        $today = date('Y-m-d');

        if (!$wallet = Wallet::select('id', 'balance')->where('customer_id', $customer_id)->first()) {
            return response()->json(['status' => 'error', 'message' => 'Wallet Not Found'], 400);
        }

        if (!$saving = Saving::where('saving_id', $request->saving_id)->first()) {
            return response()->json(['status' => 'error', 'message' => 'Saving Not Found'], 400);
        }

        if ($saving->maturity_date > $today) {
            return response()->json(['status' => 'error', 'message' => 'Savings Is Not Eligible For Withdrawal'], 400);
        }

        if ($saving->status == "1") {
            return response()->json(['status' => 'error', 'message' => 'Savings Withdrawn Already'], 400);
        }

        $wallet->balance += $saving->total_maturity_amount;
        $saving->status = "1";
        $saving->total_maturity_amount = 0;
        $saving->interest_due = 0;
        $saving->withdrawal_date = date('Y-m-d H:i:s');
        $saving->save();
        $wallet->save();

        //send mail
        try {
            $mail_data = [
                'fullname' => $customer->firstname . " " . $customer->lastname,
                'saving_id' => $saving->saving_id,
                'saving_title' => $saving->saving_title,
            ];

            $html = (new CompletedSavingsMail($mail_data))->render();
            SendGridService::sendEmail("Thank You For Saving With Us", $customer->email, $customer->firstname, $html);

        } catch (\Throwable $th) {
        }
        Helper::storeTransaction($customer_id, "Withdrew savings with saving id $request->saving_id",$saving->total_maturity_amount,'savings');
        return response()->json(['status' => 'success', 'message' => 'Savings Withdrawn Successfully'], 200);
    }

    //cron jobs

    public static function WithdrawToWalletCron()
    {
        $today = date('Y-m-d');
        $savings = Saving::where('maturity_date', '<=', $today)->where('status', "0")->take(50)->get();
        if ($savings->isEmpty()) {
            exit;
        }
        foreach ($savings as $saving) {
            if (!$wallet = Wallet::select('id', 'balance')->where('customer_id', $saving->customer_id)->first()) {
                continue;
            }
            $customer = Customer::where('customer_id',$saving->customer_id)->first();
            $wallet->balance += $saving->total_maturity_amount;
            $saving->status = "1";
            $saving->total_maturity_amount = 0;
            $saving->interest_due = 0;
            $saving->withdrawal_date = date('Y-m-d H:i:s');
            $saving->save();
            $wallet->save();

            //send mail
            try {
                $mail_data = [
                    'fullname' => $customer->firstname . " " . $customer->lastname,
                    'saving_id' => $saving->saving_id,
                    'saving_title' => $saving->saving_title,
                ];

                $html = (new CompletedSavingsMail($mail_data))->render();
                SendGridService::sendEmail("Thank You For Saving With Us", $customer->email, $customer->firstname, $html);

            } catch (\Throwable $th) {
            }
            Helper::storeTransaction($customer->customer_id, "Auto Withdrew savings with saving id $saving->saving_id",$saving->total_maturity_amount,'savings');
        }

        return "done";
    }

}
