<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\services\Helper;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TransferRecipient;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:customers', ['except' => []]);
    }

    public function dashboard()
    {
        $customer = auth('customers')->user();
        Helper::updateUpdatedAtColumn();
        $customerId = $customer->customer_id;
        $wallet_balance = Wallet::where('customer_id',$customerId)->first()->balance;

        $nextPaymentDueDate = ""; //in 21 days  //get active loan repayment and check for latest due date
        $transactionHistory = Transaction::where('customer_id',$customerId)->select('message','created_at')->orderBy('id','DESC')->take(10)->get();;

        return response()->json(['status'=>'success','wallet_balance'=>$wallet_balance,'next_payment_due_date'=>$nextPaymentDueDate,'transaction_history'=>$transactionHistory],200);
    }

    public function listBeneficiaries()
    {
        $customer_id = auth('customers')->user()->customer_id;
        Helper::updateUpdatedAtColumn();
        $results = TransferRecipient::where('customer_id',$customer_id)->get();

        return response()->json(['status'=>'success','nessage'=>'Successful', 'recipients'=>$results],200);
    }

    public function listTransactions(Request $request)
    {
        $customer = auth('customers')->user();
        Helper::updateUpdatedAtColumn();
        $customer_id = $customer->customer_id;

        $results = Transaction::query();

        if($request->search_text){
            $search_text = $request->search_text;
            $results = $results->where('message','LIKE',"%$search_text%");
        }

        if($request->start_date && $request->end_date){
            $start = "$request->start_date 00:00:00";
            $end = "$request->end_date 23:59:59";
            $results = $results->whereBetween('created_at',[$start,$end]);
        }
        $results = $results->orderBy('id','DESC')->where('customer_id',$customer_id)->paginate($request->page_size);

        return response()->json(['status'=>'success','results'=>$results],200);
    }

}
