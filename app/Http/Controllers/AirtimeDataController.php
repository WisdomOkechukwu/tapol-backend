<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\services\BaxiService;
use App\services\Helper;
use Illuminate\Http\Request;

class AirtimeDataController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:customers', ['except' => ['listDataBundles', 'buyCableTv']]);
    }

    public function listProviders(Request $request)
    {        
        Helper::updateUpdatedAtColumn();
        $providers = [];
        if ($request->type == "airtime") {
            return BaxiService::getAirtimeServiceProviders();
            if (!$providers = BaxiService::getAirtimeServiceProviders()) {
                return response()->json(['status' => 'error', 'message' => 'Error Fetching Airtime Providers'], 400);
            }
        }

        if ($request->type == "data") {
            if (!$providers = BaxiService::getDataServiceProviders()) {
                return response()->json(['status' => 'error', 'message' => 'Error Fetching Data Providers'], 400);
            }
        }

        return response()->json(['status' => 'success', 'providers' => $providers], 200);
    }

    public function listDataBundles(Request $request)
    {
        Helper::updateUpdatedAtColumn();
        if (!$result = BaxiService::getDataBundles($request->network)) {
            return response()->json(['status' => 'error', 'message' => 'Unable to get Bundles'], 400);
        }
        return $result;
    }

    public function sendAirtime(Request $request)
    {
        $customer = auth('customers')->user();
        
        Helper::updateUpdatedAtColumn();
        $wallet = Wallet::select('id', 'balance')->where('customer_id', $customer->customer_id)->first();
        if ($wallet->balance < $request->amount) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient Wallet Balance'], 400);
        }
        $ref = "TAPOL_AIRTIME" . date('YmdHis');
        if (!$result = BaxiService::sendAirtime($request->telephone, $request->amount, $request->network, $ref)) {
            return response()->json(['status' => 'error', 'message' => 'Unknown Error'], 400);
        }

        if ($result['status'] == "error") {
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        $wallet->balance -= $request->amount;
        $wallet->save();

        Helper::storeTransaction($customer->customer_id, "Made an airtime($request->network) purchase of N$request->amount",$request->amount,"debit");

        return response()->json(['status' => 'success', 'message' => 'Airtime Request Successful'], 200);
    }

    public function sendData(Request $request)
    {
        $customer = auth('customers')->user();
        Helper::updateUpdatedAtColumn();
        $wallet = Wallet::select('id', 'balance')->where('customer_id', $customer->customer_id)->first();
        if ($wallet->balance < $request->amount) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient Wallet Balance'], 400);
        }

        $ref = "TAPOL_DATA" . date('YmdHis');
        if (!$result = BaxiService::sendDataBundle($request->telephone, $request->amount, $request->network, $request->data_code, $ref)) {
            return response()->json(['status' => 'error', 'message' => 'Unknown Error'], 400);
        }

        if ($result['status'] == "error") {
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        $wallet->balance -= $request->amount;
        $wallet->save();

        Helper::storeTransaction($customer->customer_id, "Made a data($request->network) purchase of N$request->amount for data code $request->data_code",$request->amount,'data');

        return response()->json(['status' => 'success', 'message' => 'Data Request Successful'], 200);
    }

    public function listCableProviders()
    {
        Helper::updateUpdatedAtColumn();
        if (!$result = BaxiService::listCableTvProviders()) {
            return response()->json(['status' => 'error', 'message' => 'Error Getting Cable Providers'], 400);
        }

        return $result;
    }

    public function listAddons(Request $request)
    {
        Helper::updateUpdatedAtColumn();
        if (!$result = BaxiService::listAddons($request->tv)) {
            return response()->json(['status' => 'error', 'message' => 'Error Getting Addons'], 400);
        }

        return $result;
    }

    public function listProducts(Request $request)
    {
        Helper::updateUpdatedAtColumn();
        if (!$result = BaxiService::listProducts($request->tv)) {
            return response()->json(['status' => 'error', 'message' => 'Error Getting Products'], 400);
        }

        return $result;
    }

    public function buyCableTv(Request $request)
    {
        Helper::updateUpdatedAtColumn();
        $customer = auth('customers')->user();
        $wallet = Wallet::select('id', 'balance')->where('customer_id', $customer->customer_id)->first();
        if ($wallet->balance < $request->amount) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient Wallet Balance'], 400);
        }

        $amount = $request->amount;
        $total_amount = $request->amount * (int) $request->month;

        $ref = "TAPOL_CABLETV" . date('YmdHis');
        if (!$result = BaxiService::buyCableTv($request->smartcard_number, $total_amount, $request->product_code, $request->addon_code, $request->month, $request->tv, $ref)) {
            return response()->json(['status' => 'error', 'message' => 'Unknown Error'], 400);
        }

        if ($result['status'] == "error") {
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }
        $wallet->balance -= $total_amount;

        Helper::storeTransaction($customer->customer_id, "Made a cable tv($request->tv) purchase of N$request->amount with product code $request->product_code",$request->amount,'cable_tv');
        $wallet->save();

        return response()->json(['status' => 'success', 'message' => 'CABLE TV Request Successful'], 200);

    }

    public function getElectricityProvider()
    {
        Helper::updateUpdatedAtColumn();
        return BaxiService::GetElectricityProviders();
    }

    public function getElectricityCustomer(Request $request)
    {
        Helper::updateUpdatedAtColumn();
        $service_type = $request->service_type;
        $accountnumber = $request->accountnumber;
        return BaxiService::verifyElectricityName($service_type, $accountnumber);
    }

    public function buyElectricity(Request $request)
    {
        Helper::updateUpdatedAtColumn();
        $customer = auth('customers')->user();
        $wallet = Wallet::select('id', 'balance')->where('customer_id', $customer->customer_id)->first();
        if ($wallet->balance < $request->amount) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient Wallet Balance'], 400);
        }

        $ref = "TAPOL_ELECTRICITY" . date('YmdHis');

        if (!$result = BaxiService::buyElectricity($request->service_type, $request->accountnumber, $request->amount, $customer->telephone, $ref)) {
            return response()->json(['status' => 'error', 'message' => 'Unknown Error'], 400);
        }

         if ($result['status'] == "error") {
            if($result['code'] == 'BX0020'){
                return response()->json(['status' => 'error', 'message' => 'Unknown Third Party Error'], 400);
            }
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        $wallet->balance -= $request->amount;
        $wallet->save();

        Helper::storeTransaction($customer->customer_id, "Made a electricity($request->service_type) purchase of N$request->amount",$request->amount,'electricity');

        return response()->json(['status' => 'success', 'message' => 'ELECTRICITY Purchase Successful'], 200);
    }

    public function verifyAccount(Request $request)
    {
        Helper::updateUpdatedAtColumn();
        if (!$validate = BaxiService::verifyAccount($request->service_type, $request->accountnumber)) {
            return response()->json(['status' => 'error', 'message' => 'Unable To Verify Account'], 400);
        }

        if ($validate['status'] == "error") {
            return response()->json(['status' => 'error', 'message' => $validate['message']], 400);
        }

        return $validate;
    }

    public function fundBetAccount(Request $request)
    {
        $customer = auth('customers')->user();
        Helper::updateUpdatedAtColumn();
        $wallet = Wallet::select('id', 'balance')->where('customer_id', $customer->customer_id)->first();
        if ($wallet->balance < $request->amount) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient Wallet Balance'], 400);
        }

        $ref = "TAPOL_BETTING" . date('YmdHis');

        if ($result = !BaxiService::fundBettingWallet("betway", $request->accountnumber, $ref, "WALLET_FUNDING", $request->amount)) {
            return response()->json(['status' => 'error', 'message' => 'Error Funding Betway Wallet'], 400);
        }

        if ($result['status'] == "error") {
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        $wallet->balance -= $request->amount;
        $wallet->save();

        Helper::storeTransaction($customer->customer_id, "Funded Betway Account With N$request->amount",$request->amount,'bet');

        return response()->json(['status' => 'success', 'message' => 'Betway Account Funded Successfully'], 200);
    }
}
