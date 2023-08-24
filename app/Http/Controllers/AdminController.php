<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Loan;
use App\Models\User;
use App\Models\Saving;
use App\Models\Customer;
use App\services\Helper;
use App\Models\Transaction;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:users', ['except' => ['login','register']]);
    }

    public function register(Request $request)
    {
        $authid = Helper::generateRandomString(20);

        // if(trim($request->password) != trim($request->password_confirm)){
        //     return response()->json(['status'=>'error','message'=>'Passwords Do Not Match'],400);
        // } 
        if(User::where('email',$request->email)->first()){
            return response()->json(['status'=>'error','message'=>'User Exists Already'],400);
        }       

        $fields = [
            'authid' => $authid,
            'pin' => 123456,
            'email' => $request->email,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'password' => md5(123456)
        ];
        

        if (!User::create($fields)) {
            return response()->json(['status' => 'error', 'message' => 'Error Creating Customer'], 400);
        }

        return response()->json(['status' => 'success', 'message' => 'Registeration Successfully'], 200);

    }
    public function login(Request $request)
    {
        $admin = User::where('email', $request->email)
            ->where('password', md5($request->password))->first();
        if (!$admin) {
            return response(['status' => 'error', 'message' => 'Invalid details Provided'], 400);
        }

        $token = auth('customers')->setTTL('120')->login($admin);
        
        return $this->createNewToken($token);
    }

    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('customers')->factory()->getTTL(),
            'customer' => auth('customers')->user(),
            'status' => 'success',
            "message" => "Successful login",
        ], 200);
    }

    public function dashboard()
    {
        $today = date('Y-m-d');

        // Count Display:

        // Total Transaction Volume per day
        $total_transaction_amount_daily = Transaction::where('created_at', 'LIKE', "%$today%")->where('type', '!=', 'loan')->get()->sum('amount');

        // Total bill payment Volume per day
        $total_bill_payment_daily = Transaction::where('created_at', 'LIKE', "%$today%")->where('type', 'airtime')->where('type', 'data')->where('type', 'cable_tv')->where('type', 'electricity')->where('type', 'bet')->get()->sum('amount');

        // Total Loan Requests
        $total_loan_requests = Loan::where('status', "0")->get()->count();

        // Total Loans Due
        // $total_loan_due = Loan::where('status',"5")->get()->count();
        $total_loan_due = 0;

        // Yearly Savings Volume
        $this_year = date('Y');
        $yearly_savings_total_amount = Saving::where('created_at', 'LIKE', "%$this_year%")->get()->sum('amount_to_save');
        // Yearly Loans Volume
        $yearly_loan_total_amount = Loan::where('status', '1')->where('created_at', 'LIKE', "%$this_year%")->get()->sum('amount');

        // Count Total User Signups
        $total_users = Customer::all()->count();

        // Get the current timestamp
        $now = Carbon::now();

        // Get the users who have been active in the last 5 minutes
        $total_online_users = Customer::where('updated_at', '>=', $now->subMinutes(5))->get()->count();


        // Count Total Transaction per day
        $total_transaction_count_daily = Transaction::where('created_at', 'LIKE', "%$today%")->where('type', '!=', 'loan')->get()->count();

        // Count total Bill Payments per day
        $total_bill_count_daily = Transaction::where('created_at', 'LIKE', "%$today%")->where('type', 'airtime')->where('type', 'data')->where('type', 'cable_tv')->where('type', 'electricity')->where('type', 'bet')->get()->count();

        return response()->json(['status' => 'success', 'total_transaction_amount_daily' => $total_transaction_amount_daily, 'total_bill_payment_daily' => $total_bill_payment_daily, 'total_loan_requests' => $total_loan_requests, 'total_loan_due' => $total_loan_due, 'yearly_savings_total_amount' => $yearly_savings_total_amount, 'yearly_loan_total_amount' => $yearly_loan_total_amount, 'total_users' => $total_users, 'total_online_users' => $total_online_users, 'total_transaction_count_daily' => $total_transaction_count_daily, 'total_bill_count_daily' => $total_bill_count_daily], 200);
    }

    public function pieChart()
    {
        // PieChat Bill View:
        // Airtime Purchase
        // Data Purchase
        // Internet Bill Purchase
        // Waec.Jamb Purchase
        // TV subscription Purchase

        //get total bill payment tot get total

    }

    public function logout()
    {
        auth('users')->logout();

        return response()->json(['status'=>'success','message' => 'Successfully logged out'],200);
    }

    public function adminProfile()
    {
        $admin = auth('users')->user();
        if(!$profile = User::where('authid',$admin->authid)->first()){
            return response()->json(['status'=>'error','message'=>'Admin Not Found'],400);
        }

        return response()->json(['status'=>'success','admin'=>$profile],200);

    }

    public function ListCustomers()
    {
        $customers = Customer::select('id','firstname','lastname','email','gender','telephone','company_name')->get();

        return response()->json(['status'=>'success','message'=>'successful','customers'=>$customers],200);
    }

    public function oneCustomer(Request $request)
    {
        if(!$customer = Customer::where('id',$request->id)->with('wallet','transactions','loans')->first()){
            return response()->json(['status'=>'error','message'=>'Customer not found'],400);
        }

        return response()->json(['status'=>'success','message'=>'successful','customer'=>$customer],200);
    }
}
