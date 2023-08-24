<?php
namespace App\services;

use Illuminate\Support\Facades\Http;

class BaxiService
{

    public static function getAirtimeServiceProviders()
    {
        $url = env('BAXI_BASE_URL') . '/airtime/providers';

        $data = Http::withHeaders([
            'Accept' => 'application/json',
            'x-api-key' => env('BAXI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->get($url)->json();

        if ($data['code'] == 200) {
            return $data;
        } else {
            return false;
        }

    }

    public static function sendAirtime($telephone, $amount, $network, $reference)
    {
        $url = env('BAXI_BASE_URL') . '/airtime/request';

        $field = [
            'phone' => $telephone,
            'amount' => $amount,
            'service_type' => $network,
            'plan' => 'prepaid',
            'agentReference' => $reference,
        ];

        try {
            $data = Http::withHeaders([
                'Accept' => 'application/json',
                'x-api-key' => env('BAXI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post($url, $field)->json();
            return [
                'url' => $url,
                'payload' => $field,
                'reference' => $reference,
                'response' => $data,
            ];
            return $data;
        } catch (\Throwable $th) {
            return false;
        }

        return false;

    }

    public static function getDataServiceProviders()
    {
        $url = env('BAXI_BASE_URL') . '/databundle/providers';

        $data = Http::withHeaders([
            'Accept' => 'application/json',
            'x-api-key' => env('BAXI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->get($url)->json();

        if ($data['code'] == 200) {
            return $data;
        } else {
            return false;
        }
    }
    public static function getDataBundles($network, $accountnumber = null)
    {

        $url = env('BAXI_BASE_URL') . '/databundle/bundles';

        $field = [
            'service_type' => $network,
            'account_number' => $accountnumber,
        ];

        $data = Http::withHeaders([
            'Accept' => 'application/json',
            'x-api-key' => env('BAXI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post($url, $field)->json();

        if ($data['code'] == 200) {
            return $data;
        } else {
            return false;
        }
    }

    public static function sendDataBundle($telephone, $amount, $network, $data_code, $reference)
    {
        $url = env('BAXI_BASE_URL') . '/databundle/request';

        $field = [
            'phone' => $telephone,
            'amount' => $amount,
            'service_type' => $network,
            'datacode' => $data_code,
            'agentReference' => $reference,
        ];

        try {
            $data = Http::withHeaders([
                'Accept' => 'application/json',
                'x-api-key' => env('BAXI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post($url, $field)->json();
            return $data;
        } catch (\Throwable $th) {
            return false;
        }

        return false;
    }

    public static function listCableTvProviders()
    {
        $url = env('BAXI_BASE_URL') . '/cabletv/providers';

        $data = Http::withHeaders([
            'Accept' => 'application/json',
            'x-api-key' => env('BAXI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->get($url)->json();

        if ($data['code'] == 200) {
            return $data;
        } else {
            return false;
        }
    }

    public static function listAddons($tv)
    {
        $url = env('BAXI_BASE_URL') . '/multichoice/addons';
        $field = [
            'service_type' => $tv,
            'product_code' => "NNJ2E36",
        ];

        $data = Http::withHeaders([
            'Accept' => 'application/json',
            'x-api-key' => env('BAXI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post($url, $field)->json();

        if ($data['code'] == 200) {
            return $data;
        } else {
            return false;
        }
    }
    public static function listProducts($tv)
    {
        $url = env('BAXI_BASE_URL') . '/multichoice/list';
        $field = [
            'service_type' => $tv,
        ];

        $data = Http::withHeaders([
            'Accept' => 'application/json',
            'x-api-key' => env('BAXI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post($url, $field)->json();

        if ($data['code'] == 200) {
            return $data;
        } else {
            return false;
        }
    }

    public static function buyCableTv($smartcard_number, $total_amount, $product_code, $addon_code = null, $months, $tv, $reference)
    {
        $url = env('BAXI_BASE_URL') . '/multichoice/request';
        $field = [
            'smartcard_number' => $smartcard_number,
            'total_amount' => $total_amount,
            'product_code' => $product_code,
            'addon_code' => $addon_code,
            'product_monthsPaidFor' => $months,
            'service_type' => $tv,
            'agentReference' => $reference,
        ];

        try {
            $data = Http::withHeaders([
                'Accept' => 'application/json',
                'x-api-key' => env('BAXI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post($url, $field)->json();

           return $data;
        } catch (\Throwable $th) {
            return false;
        }

        return false;
    }

    public static function GetElectricityProviders()
    {
        $url = env('BAXI_BASE_URL') . '/electricity/billers';

        $data = Http::withHeaders([
            'Accept' => 'application/json',
            'x-api-key' => env('BAXI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->get($url)->json();

        if ($data['code'] == 200) {
            return $data;
        } else {
            return false;
        }
    }

    public static function verifyElectricityName($service_type, $accountnumber)
    {
        $url = env('BAXI_BASE_URL') . '/electricity/verify';

        $field = [
            "service_type" => $service_type,
            "account_number" => $accountnumber,
        ];

        $data = Http::withHeaders([
            'Accept' => 'application/json',
            'x-api-key' => env('BAXI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post($url, $field)->json();

        if ($data['code'] == 200) {
            return $data;
        } else {
            return false;
        }
    }

    public static function buyElectricity($service_type,$accountnumber,$amount,$telephone,$reference)
    {
        $url = env('BAXI_BASE_URL') . '/electricity/request';
        $field = [
            "service_type" => $service_type,
            "account_number" => $accountnumber,
            "amount" => $amount,
            "metadata" => "",
            "phone" => $telephone,
            "agentReference" => $reference,
        ];

        try {
            $data = Http::withHeaders([
                'Accept' => 'application/json',
                'x-api-key' => env('BAXI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post($url, $field)->json();
            return $data;
        } catch (\Throwable $th) {
            return false;
        }
        return false;
    }

    public static function fundBettingWallet($service_type="betway",$accountnumber,$ref,$action="WALLET_FUNDING",$amount)
    {
        $url = env('BAXI_BASE_URL') . '/betting/request';
        $field = [
            "service_type" => $service_type,
            "account_number" => $accountnumber,
            "amount" => $amount,
            'action' => $action,
            "agentReference" => $ref,
        ];

        try {
            $data = Http::withHeaders([
                'Accept' => 'application/json',
                'x-api-key' => env('BAXI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post($url, $field)->json();
            return $data;
        } catch (\Throwable $th) {
            return false;
        }
        return false;
    }

    public static function verifyAccount($service_type,$accountnumber)
    {
        $url = env('BAXI_BASE_URL') . '/namefinder/query';
        $field = [
            "service_type" => $service_type,
            "account_number" => $accountnumber,
        ];

        try {
            $data = Http::withHeaders([
                'Accept' => 'application/json',
                'x-api-key' => env('BAXI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post($url, $field)->json();
            return $data;
        } catch (\Throwable $th) {
            return false;
        }

        return false;

    }

    public static function reQuery($reference)
    {
        $url = env('BAXI_BASE_URL') . "/superagent/transaction/requery?agentReference=$reference";
        try {
            $data = Http::withHeaders([
                'Accept' => 'application/json',
                'x-api-key' => env('BAXI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->get($url)->json();
            // return [
            //     'url' => $url,
            //     'reference' => $reference,
            //     'response' => $data,
            // ];
            return $data;
        } catch (\Throwable $th) {
            return false;
        }

        return false;
    }
}
