<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Setting;
use App\Models\Customer;
use App\services\Helper;
use App\Mail\AdminNewLoan;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Mail\NewLoanSuccessful;
use App\services\SendGridService;
use App\Http\Requests\loan\ApplyRequest;
use App\Http\Requests\loan\LoanCalculatorRequest;
use App\Models\LoanRepayment;
use App\Models\Wallet;

class LoanController extends Controller
{
    // 0 -new loan 1- approved 2-reject
    public function __construct()
    {
        $this->middleware('auth:customers', ['except' => ['sendNotificationMail']]);
    }

    public function loanCalculator(LoanCalculatorRequest $request)
    {
        $amount = $request->amount;
        $duration = $request->duration;

        $interest_rate = 0.1;
        //10% personal loans 8% business loans
        $purpose = $request->purpose;
        if ($purpose == 'Pay School Fees' || $purpose == 'Consumption loan' || $purpose == 'Travel expenses loan' || $purpose == 'Consumption loan' || $purpose == 'Rent loan') {
            $interest_rate = $interest_rate;
        }

        if ($purpose == 'Business loan' || $purpose == 'Education loan' || $purpose == 'Health care loan' || $purpose == 'Pension loan') {
            $interest_rate = 0.08;
        }

        $monthly_interest = $interest_rate * $amount;
        $total_repayment = $interest_rate * $amount * $duration + $amount;
        $monthly_repayment = $total_repayment / $duration;
        $monthly_repayment = number_format((float) $monthly_repayment, 2, '.', '');
        return response()->json(['status' => 'success', 'interest_rate' => $interest_rate, 'amount' => $amount, 'duration' => $duration, 'monthly_interest' => $monthly_interest, 'total_repayment' => $total_repayment, 'monthly_repayment' => $monthly_repayment], 200);
    }

    public function apply(ApplyRequest $request)
    {
        $customerId = $request->customer_id;
        Helper::updateUpdatedAtColumn();
        if (!$customerId) {
            $customerId = auth('customers')->user()->customer_id;
        }
        if (
            !($customer = Customer::select('id', 'firstname', 'email', 'lastname')
                ->where('customer_id', $customerId)
                ->first())
        ) {
            return response()->json(['status' => 'error', 'message' => 'Customer Not Found'], 400);
        }

        if (
            Loan::where('status', 0)
                ->where('customer_id', $customerId)
                ->first()
        ) {
            return Helper::getResponseMessage(400, 'You have a loan waiting for approval', 'error');
        }

        $loanid = 'LN' . date('YmdHis');

        $field = [
            'loanid' => $loanid,
            'customer_id' => $customerId,
            'amount' => $request->amount,
            'duration' => $request->duration,
            'monthly_repayment' => $request->monthly_repayment,
            'loan_purpose' => $request->loan_purpose,
            'total_repayment' => $request->total_repayment,
            'monthly_interest' => $request->monthly_interest,
        ];

        if (Loan::create($field)) {
            $transactions = new Transaction();
            $transactions->customer_id = $customerId;
            $transactions->message = "Applied for loan of N$request->amount";
            $transactions->type = 'loan';
            $transactions->amount = $request->amount;
            $transactions->save();
        }

        try {
            $mail_data = [
                'firstname' => $customer->firstname,
                'amount' => $request->amount,
                'monthly_repayment' => $request->monthly_repayment,
                'duration' => $request->duration,
            ];
            $html = (new NewLoanSuccessful($mail_data))->render();
            SendGridService::sendEmail('Loan Application Successful', $customer->email, $customer->firstname, $html);
        } catch (\Throwable $th) {
        }

        try {
            $admin_email = 'info@tapolgroup.com';
            $admin_firstname = 'Tapol';
            $mail_data = [
                'firstname' => $admin_firstname,
                'customer_name' => "$customer->firstname $customer->lastname",
                'loanid' => $loanid,
                'amount' => $request->amount,
                'monthly_repayment' => $request->monthly_repayment,
                'duration' => $request->duration,
            ];
            $html = (new AdminNewLoan($mail_data))->render();
            SendGridService::sendEmail('New Loan Application Awaiting Approval', $admin_email, $admin_firstname, $html);
        } catch (\Throwable $th) {
            return $th;
        }
        $customer->loan_status = 1;
        $customer->save();
        return Helper::getResponseMessage(200, 'Loan Application Successful', 'success');
    }

    public function sendNotificationMail(Request $request)
    {
        try {
            // to the admin
            $customer = Customer::where('customer_id', $request->customer_id)->first();
            $loanid = $request->loanid;
            $type = $request->type === 'approve' ? 'Loan Approved' : 'Loan Declined';

            // $admin_email = 'thewisdomokechukwu@gmail.com';
            $admin_email = "info@tapolgroup.com";
            $admin_firstname = 'Tapol';
            $mail_data = [
                'message' => $request->message,
                'firstname' => $admin_firstname,
                'customer_name' => "$customer->firstname $customer->lastname",
                'loanid' => $loanid,
                'amount' => $request->amount,
                'monthly_repayment' => $request->monthly_repayment,
                'duration' => $request->duration,
            ];
            $html = (new AdminNewLoan($mail_data))->render();
            SendGridService::sendEmail($type, $admin_email, $admin_firstname, $html);

            $mail_data = [
                'message' => $request->message,
                'firstname' => $customer->firstname,
                'amount' => $request->amount,
                'monthly_repayment' => $request->monthly_repayment,
                'duration' => $request->duration,
            ];
            $html = (new NewLoanSuccessful($mail_data))->render();
            // SendGridService::sendEmail($type, $admin_email, $customer->firstname, $html);
            SendGridService::sendEmail($type,$customer->email,$customer->firstname, $html);

            return response()->json(['status' => 'success'], 200);
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function listLoans(Request $request)
    {
        $customerId = auth('customers')->user()->customer_id;
        Helper::updateUpdatedAtColumn();
        $loans = Loan::query();

        if ($search_text = $request->search_text) {
            $loans = $loans->where('loanid', $search_text);
        }

        if ($request->start_date && $request->end_date) {
            $start = "$request->start_date 00:00:00";
            $end = "$request->end_date 23:59:59";
            $loans = $loans->whereBetween('created_at', [$start, $end]);
        }

        if ($request->status) {
            $status = $request->status;
            $loans = $loans->where('status', $status);
        }

        $activeLoans = Loan::where('customer_id', $customerId)
            ->where('status', 1)
            ->get();
        $waitingLoans = Loan::where('customer_id', $customerId)
            ->where('status', 0)
            ->get();

        $loans = $loans
            ->orderBy('id', 'DESC')
            ->where('customer_id', $customerId)
            ->paginate($request->page_size);
        return response()->json(['status' => 'success', 'loans' => $loans, 'active' => $activeLoans, 'waiting' => $waitingLoans], 200);
    }

    public function oneLoan(Request $request)
    {
        if (!($loan = Loan::where('id', $request->id)->first())) {
            return response()->json(['status' => 'error', 'message' => 'Loan Does Not Exist'], 400);
        }
        //
        return response()->json(['status' => 'success', 'loan' => $loan], 400);
    }

    public function partRepayment(Request $request)
    {
        $customer = Customer::where('email', $request->email)->first();
        if (!$customer) {
            return response()->json(['status' => 'error', 'message' => 'Customer Not Found'], 400);
        }

        $loan = Loan::where('loanid', $request->loan)->first();
        if (!$loan) {
            return response()->json(['status' => 'error', 'message' => 'Loan Does Not Exist'], 400);
        }

        $wallet = Wallet::select('id', 'balance')
            ->where('customer_id', $customer->customer_id)
            ->first();
        if (!$wallet) {
            return response()->json(['status' => 'error', 'message' => 'Wallet Not Found'], 400);
        }

        if($request->amount > ($loan->total_repayment - $loan->paid_amount)){
            return response()->json(['status' => 'error', 'message' => 'Amount exceeded loan amount'], 400);
        }

        if($request->amount > $wallet->balance){
            return response()->json(['status' => 'error', 'message' => 'wallet balance low'], 400);
        }
        $wallet->balance -= $request->amount;
        $wallet->save();

        $loan->paid_amount += $request->amount;
        if(($loan->total_repayment - $loan->paid_amount) == 0){
            $loan->status = 3;
        }
        $loan->save();

        $loanRepayment = new LoanRepayment();
        $loanRepayment->loanid = $loan->loanid;
        $loanRepayment->customer_id = $customer->customer_id;
        $loanRepayment->amount = $request->amount;
        $loanRepayment->payment_date = now();
        $loanRepayment->save();

        $message = "loan payment successful";
        return response()->json(['status' => 'success', 'message' => ucwords($message), 'loan'=>[$loan]], 200);
    }
}
