<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\services\Helper;
use App\Mail\ForgotPassword;
use Illuminate\Http\Request;
use App\services\TwiloService;
use App\services\WalletService;
use App\services\PaystackService;
use App\services\SendGridService;
use Illuminate\Support\Facades\DB;
use App\Mail\WelcomeMessageWithPassword;
use App\Http\Requests\customerauth\EditProfileRequest;
use App\Http\Requests\customerauth\LoginCustomerRequest;
use App\Http\Requests\customerauth\ResetPasswordRequest;
use App\Http\Requests\customerauth\ChangePasswordRequest;
use App\Http\Requests\customerauth\ForgotPasswordRequest;
use App\Http\Requests\customerauth\RegisterCustomerRequest;
use App\Http\Requests\customerauth\EditProfilePictureRequest;
use App\Models\VerificationCode;
use Exception;

class CustomerAuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:customers', ['except' => ['registerCustomer', 'login', 'forgotPassword','resetPassword','verifyCustomer']]);
    }

    public function registerCustomer(RegisterCustomerRequest $request)
    {
        $customer_id = "CST".date('YmdHis');


        $validate_password = Helper::validatePassword($request->password);
        if($validate_password['status'] == "error"){
            return response()->json(['status' => 'error','message'=>$validate_password['message']],400);
        }

        if(trim($request->password) != trim($request->password_confirm)){
            return response()->json(['status'=>'error','message'=>'Passwords Do Not Match'],400);
        }

        $fields = [
            'customer_id' => $customer_id,
            'pin' => $request->password,
            'email' => $request->email,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'gender' => $request->gender,
            'marital_status' => $request->marital_status,
            'residential_address' => $request->residential_address,
            'employment_status' => $request->employment_status,
            'company_name' => $request->company_name,
            'company_location' => $request->company_location,
            'city' => $request->city,
            'bvn' => $request->bvn,
            'telephone' => $request->telephone,
            'bankname' => $request->bankcode,
            'accountnumber' => $request->accountnumber,
            'password' => md5($request->password)
        ];
        $bankdata = PaystackService::validateAccountNumber(trim($request->accountnumber), trim($request->bankcode));
        if ($bankdata) {
            if ($bankdata["status"] != "success") {
                return response()->json(['status' => 'error', 'message' => 'Invalid Bank Details'], 400);
            }
        } else {
            return response()->json(['status' => 'error', 'message' => 'Invalid Bank Details'], 400);
        }
        $name = "$request->firstname $request->lastname";
        if(!WalletService::createWallet($customer_id, $name, $request->accountnumber, $request->bankcode)){
            return response()->json(['status' => 'error', 'message' => 'Error Creating Wallet'], 400);
        }

        if (!$customer = Customer::create($fields)) {
            return response()->json(['status' => 'error', 'message' => 'Error Creating Customer'], 400);
        }

        $token = Helper::getVerificationCode();

        //trigger email  //application successful, kindly check email for password and change password
        try {
            $mail_data = [
                'firstname' => $customer->firstname,
                'customer_id' => $customer->customer_id,
                'unique' => $token
            ];
            $html = (new WelcomeMessageWithPassword($mail_data))->render();
            SendGridService::sendEmail("Welcome To Tapol",$customer->email,$customer->firstname, $html);
        } catch (\Throwable $th) {
            $customer->email_error = $th;
            $customer->save();
        }


        return response()->json(['status' => 'success', 'message' => 'Registeration Successfully, Kindly verify your account'], 200);

    }

    public function verifyCustomer(Request $request)
    {
        $customer_id = $request->customer_id;
        $unique = $request->unique;
        if(!VerificationCode::where('code',$unique)->first()){
            return response()->json(['status'=>'error','message'=>'Invalid Code'],400);
        }

        if(!$customer = Customer::where('customer_id',$customer_id)->first()){
            return response()->json(['status'=>'error','message'=>'Customer Not Found'],400);
        }
        $customer->verify_email = "1";
        $customer->save();
        return redirect('https://app.tapolgroup.com/verified');
    }

    public function login(LoginCustomerRequest $request)
    {
        $customer = Customer::where('email', $request->email)
            ->where('password', md5($request->password))->first();
        if (!$customer) {
            return response(['status' => 'error', 'message' => 'Invalid details Provided'], 400);
        }

        if($customer->verify_email != "1"){
            $token = Helper::getVerificationCode();
            try {
                $mail_data = [
                    'firstname' => $customer->firstname,
                    'customer_id' => $customer->customer_id,
                    'unique' => $token
                ];
                $html = (new WelcomeMessageWithPassword($mail_data))->render();
                SendGridService::sendEmail("Welcome To Tapol",$customer->email,$customer->firstname, $html);
            } catch (\Throwable $th) {
            }
            return response()->json(['status'=>'error','message'=>'Account Not Verified, Kindly Check Your Email'],400);
        }
        $token = auth('customers')->setTTL('120')->login($customer);
        // $customer->forgot_password_status = "0";
        // $customer->save();
        Helper::updateUpdatedAtColumn();
        return $this->createNewToken($token);
    }

    // public function

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

    public function changePassword(ChangePasswordRequest $request)
    {
        $customer = auth('customers')->user();
        Helper::updateUpdatedAtColumn();
        $id = $request->id;
        if(!$id){
            $id = $customer->id;
        }
        if (!$customer = Customer::select('id','pin','password','forgot_password_status')->where('id', $id)->first()) {
            return response()->json(['status' => 'error', 'message' => 'Customer Not Found'], 400);
        }

        $old_password = md5($request->old_password);
        if ($customer->password != $old_password) {
            return response()->json(['status' => 'error', 'message' => 'Incorrect Old Password'], 400);
        }
        $validate_password = Helper::validatePassword($request->new_password);
        if($validate_password['status'] == "error"){
            return response()->json(['status' => 'error','message'=>$validate_password['message']],400);
        }
        $customer->pin = $request->new_password;
        $customer->password = md5($request->new_password);
        $customer->forgot_password_status = "0";
        $customer->save();
        return response(['status' => 'success', 'message' => 'Password Successfully changed'], 200);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        if (!$customer = Customer::select('id','pin','password','email','firstname','telephone')->where('email', $request->email)->first()) {
            return response()->json(['status' => 'error', 'message' => 'Email Is Not Registered With Tapol'], 400);
        }
        $new_password = Helper::generatePassword(15);
        $new_password_auth = md5($new_password);
        $customer->pin = $new_password;
        $customer->password = $new_password_auth;
        $customer->forgot_password_status = "1";
        $customer->save();
        try {
            $mail_data = [
                'firstname' => $customer->firstname,
                'password' => $new_password
            ];
            $html = (new ForgotPassword($mail_data))->render();
            SendGridService::sendEmail("New Password",$customer->email,$customer->firstname, $html);
        } catch (\Throwable $th) {
            try {
                $tel = trim($customer->telephone,'0');
                $body = "Your New password is $new_password, Kindly Login";
                TwiloService::sendSMS($tel,$body);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Your Reset Password Has Been Sent To Your Email'], 200);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        if(!$customer = Customer::select('id','pin','password')->where('customer_id',$request->customer_id)->first()){
            return response()->json(['status' => 'error', 'message' => 'Customer Not Found'], 400);
        }
        $pin = 123456;
        $customer->pin = $pin;
        $customer->password = md5($pin);
        $customer->save();
        return response()->json(['status'=>'success','message'=>'Password Reset Successfully'],200);
    }

    public function editProfilePicture(EditProfilePictureRequest $request)
    {
        $customer_id = auth('customers')->user()->customer_id;
        Helper::updateUpdatedAtColumn();
        if(!$customer = Customer::select('id','profile_picture')->where('customer_id',$customer_id)->first()){
            return response()->json(['status' => 'error', 'message' => 'Customer Not Found'], 400);
        }
        $profile_picture = $request->profile_picture;
        $documentName = time() . '.' . $profile_picture->extension();
        $profile_picture->move('storage/documents/profile_pictures/', $documentName);

        $customer->profile_picture = $documentName;
        $customer->save();
        return response()->json(['status' => "success", "message" => "Profile Picture Uploaded Successfully"], 200);
    }

    public function customerProfile()
    {
        $customer = auth('customers')->user();
        Helper::updateUpdatedAtColumn();

        if(!$customer = Customer::select('customer_id','email','firstname','lastname','gender','marital_status','residential_address','profile_picture','employment_status','company_name','company_location','city','bvn','telephone','forgot_password_status')->where('customer_id',$customer->customer_id)->with('wallet:customer_id,balance','loans','transactions','waitingLoans','activeLoans')->first()){
            return response()->json(['status' => 'error', 'message' => "Customer Not Found"], 400);
        }
        return response()->json(['status' => 'success', 'customer' => $customer], 200);
    }

    public function editProfile(EditProfileRequest $request)
    {
        $customer_id = auth('customers')->user()->customer_id;
        Helper::updateUpdatedAtColumn();
        if(!$customer = Customer::select('id','email','firstname','lastname','gender','marital_status','residential_address','employment_status','company_name','company_location','city','bvn','telephone')->where('customer_id',$customer_id)->first()){
            return response()->json(['status' => 'error', 'message' => 'Customer Not Found'], 400);
        }

        $field = [
            'email' => $request->email,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'gender' => $request->gender,
            'marital_status' => $request->marital_status,
            'residential_address' => $request->residential_address,
            'employment_status' => $request->employment_status,
            'company_name' => $request->company_name,
            'company_location' => $request->company_location,
            'city' => $request->city,
            'bvn' => $request->bvn,
            'telephone' => $request->telephone
        ];

        $customer->update($field);
        return response()->json(['status' => 'success', 'message' => 'Customer Edited Successfully'], 200);
    }

    public function logout()
    {
        auth('customers')->logout();

        return response()->json(['status'=>'success','message' => 'Successfully logged out'],200);
    }


}
