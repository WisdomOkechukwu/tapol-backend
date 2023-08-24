<?php

use App\Http\Controllers\AdminController;
use App\services\Automation;
use Illuminate\Http\Request;
use App\services\BaxiService;
use App\services\TwiloService;
use App\services\PaystackService;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\SavingController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AirtimeDataController;
use App\Http\Controllers\CustomerAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('customerauth')->group(function () {
    Route::post('register', [CustomerAuthController::class, 'registerCustomer']);
    Route::post('login', [CustomerAuthController::class, 'login']);
    Route::post('forgot-password', [CustomerAuthController::class, 'forgotPassword']);
    Route::post('change-password', [CustomerAuthController::class, 'changePassword']);
    Route::post('reset-password', [CustomerAuthController::class, 'resetPassword']);
    Route::get('profile', [CustomerAuthController::class, 'CustomerProfile']);
    Route::post('profile/edit', [CustomerAuthController::class, 'editProfile']);
    Route::post('picture/change', [CustomerAuthController::class, 'editProfilePicture']);
    Route::get('logout', [CustomerAuthController::class, 'logout']);
    Route::get('verify', [CustomerAuthController::class, 'verifyCustomer']);
});

Route::prefix('customer')->group(function () {
    Route::get('dashboard', [CustomerController::class, 'dashboard']);
    Route::post('transactions/list', [CustomerController::class, 'listTransactions']);
    Route::get('beneficiaries/list', [CustomerController::class, 'listBeneficiaries']);
});
Route::prefix('loan')->group(function () {
    Route::post('calculate', [LoanController::class, 'loanCalculator']);
    Route::post('apply', [LoanController::class, 'apply']);
    Route::post('list', [LoanController::class, 'listLoans']);
    Route::post('one', [LoanController::class, 'oneLoan']);
    Route::post('repayment/part', [LoanController::class, 'partRepayment']);
    Route::post('mailer/send', [LoanController::class, 'sendNotificationMail']);
});

Route::prefix('wallet')->group(function () {
    Route::post('create', [WalletController::class, 'createWallet']);
    Route::post('edit', [WalletController::class, 'editWallet']);
    Route::post('deposit/initiate', [WalletController::class, 'initializeDeposit']);
    Route::get('deposit/finalize', [WalletController::class, 'confirmDeposit']);
    Route::post('withdrawal/initiate', [WalletController::class, 'Initiatewithdraw']);
    Route::post('withdrawal/verify', [WalletController::class, 'verifyWithdrawal']);
});

Route::prefix('otp')->group(function () {
    Route::post('send', [OtpController::class, 'sendOtp']);
    Route::post('verify', [OtpController::class, 'verifyOtp']);
});

Route::prefix('baxi')->group(function () {
    Route::post('airtime-data/providers', [AirtimeDataController::class, 'listProviders']);
    Route::post('data-bundles', [AirtimeDataController::class, 'listDataBundles']);
    Route::post('airtime/send', [AirtimeDataController::class, 'sendAirtime']);
    Route::post('data/send', [AirtimeDataController::class, 'sendData']);
    Route::get('cable/providers', [AirtimeDataController::class, 'listCableProviders']);
    Route::post('cable/addons', [AirtimeDataController::class, 'listAddons']);
    Route::post('cable/products', [AirtimeDataController::class, 'listProducts']);
    Route::post('cable/buy', [AirtimeDataController::class, 'buyCableTv']);
    Route::get('electricity/providers', [AirtimeDataController::class, 'getElectricityProvider']);
    Route::post('electricity/buy', [AirtimeDataController::class, 'buyElectricity']);
    Route::post('account/verify', [AirtimeDataController::class, 'verifyAccount']);
    Route::post('bet/fund', [AirtimeDataController::class, 'fundBetAccount']);
});
Route::prefix('utility')->group(function () {
    Route::get('banks', [UtilityController::class, 'getBanks']);
});


Route::prefix('saving')->group(function () {
    Route::post('initiate', [SavingController::class, 'initiateSavings']);
    Route::post('start', [SavingController::class, 'createSavings']);
    Route::post('list', [SavingController::class, 'listSavings']);
    Route::post('withdraw', [SavingController::class, 'withdrawSavings']);
    Route::get('withdraw/cron', [SavingController::class, 'WithdrawToWalletCron']);
    Route::post('topup', [SavingController::class, 'topUpSaving']);
});


////////////////////////////////////////////////////////////////////////////////////////////////////
//ADMIN
Route::prefix('admin')->group(function () {
    Route::get('dashboard', [AdminController::class, 'dashboard']);
    Route::post('login', [AdminController::class, 'login']);
    Route::post('register', [AdminController::class, 'register']);
    Route::get('profile', [AdminController::class, 'adminProfile']);
    Route::get('customers/list', [AdminController::class, 'ListCustomers']);
    Route::post('customer/one', [AdminController::class, 'oneCustomer']);
});


////////////////////////////////////////////////////////////////////////////////////////////////////

Route::prefix('test')->group(function(){

    Route::post('verify-email',[TestController::class, 'verifyEmail']);
    Route::post('verify-account',[TestController::class, 'validateAccountNumber']);
    Route::post('create-paystack-recipient',[TestController::class, 'createPaystackRecipient']);
    Route::post('email/send',[TestController::class, 'sendEmail']);
    Route::post('sms/send',[TestController::class, 'sendSms']);
    Route::post('paystack/initialize/deposit',[TestController::class, 'initializePayment']);
    Route::post('baxi/find',[AirtimeDataController::class, 'getElectricityCustomer']);
});

Route::get('cron',function(){
    return Automation::verifyPayment();
});

Route::get('testt',function(){
    return BaxiService::reQuery("TAPOL_AIRTIME20230317170931");
    return BaxiService::getAirtimeServiceProviders();
    $ref = date('YmdHis');
    return BaxiService::sendAirtime("07088555978",200,"airtel",$ref);
    return PaystackService::chargeCard("abiodunflb20@gmail.com",100,"AUTH_71zn8r86gz");
    return TwiloService::createVerificationService();
    return TestController::sendEmaill();
    return PaystackService::verifyTransfer("wdwl20220821011016");


    return BaxiService::getAirtimeServiceProviders();
});

