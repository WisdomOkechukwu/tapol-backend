<?php
namespace App\services;

use Illuminate\Support\Facades\Http;

class PaystackService
{

    public static function validateAccountNumber($accountNumber, $bankCode)
    {
        $url = "https://api.paystack.co/bank/resolve?account_number=" . $accountNumber . "&bank_code=" . $bankCode;
        $data = Http::withHeaders([
            'Accept' => 'application/json',
            // 'Authorization' => 'Bearer sk_test_b4a6648ab7cf38b99521ca9e65c2f12cf7da11ae',
            'Authorization' => 'Bearer sk_live_e45de44e3028fd6bb13e24aaf69b9fce74babe53',
            'Content-Type' => 'application/json',
        ])->get($url)->json();
        if ($data['status'] == false) {
            return false;
        } else {
            $newdata['status'] = "success";
            $body['accountnumber'] = $data['data']['account_number'];
            $body['account_name'] = $data['data']['account_name'];
            $newdata['data'] = $body;
            return $newdata;
        }
    }

    public static function getBanks()
    {
        $url = "https://api.paystack.co/bank";
        $data = Http::withHeaders([
            'Accept' => 'application/json',
            // 'Authorization' => 'Bearer sk_test_b4a6648ab7cf38b99521ca9e65c2f12cf7da11ae',
            'Authorization' => 'Bearer sk_live_e45de44e3028fd6bb13e24aaf69b9fce74babe53',
            'Content-Type' => 'application/json',
        ])->get($url)->json();
        if ($data['status'] == false) {
            return false;
        } else {
            return $data;
        }
    }

    public static function createRecipient($name, $accountnumber, $bankcode)
    {
        $url = "https://api.paystack.co/transferrecipient";

        $field = [
            "type" => "nuban",
            "name" => $name,
            "account_number" => $accountnumber,
            "bank_code" => $bankcode,
            "currency" => "NGN",
        ];

        $data = Http::withHeaders([
            'Accept' => 'application/json',
            // 'Authorization' => 'Bearer sk_test_b4a6648ab7cf38b99521ca9e65c2f12cf7da11ae',
            'Authorization' => 'Bearer sk_live_e45de44e3028fd6bb13e24aaf69b9fce74babe53',
            'Content-Type' => 'application/json',
        ])->post($url, $field)->json();

        if ($data['status'] == false) {
            return false;
        } else {
            return $data;
        }
    }

    public static function initializePayment($amount, $email, $reference_id)
    {
        $url = "https://api.paystack.co/transaction/initialize";
        $field = [
            'amount' => $amount * 100,
            'email' => $email,
            'reference' => $reference_id,
        ];

        $data = Http::withHeaders([
            'Accept' => 'application/json',
            // 'Authorization' => 'Bearer sk_test_b4a6648ab7cf38b99521ca9e65c2f12cf7da11ae',
            'Authorization' => 'Bearer sk_live_e45de44e3028fd6bb13e24aaf69b9fce74babe53',
            'Content-Type' => 'application/json',
        ])->post($url, $field)->json();

        if ($data['status'] == false) {
            return false;
        } else {
            return $data;
        }
    }

    public static function verifyPayment($reference_id)
    {
        $url = "https://api.paystack.co/transaction/verify/$reference_id";

        $data = Http::withHeaders([
            'Accept' => 'application/json',
            // 'Authorization' => 'Bearer sk_test_b4a6648ab7cf38b99521ca9e65c2f12cf7da11ae',
            'Authorization' => 'Bearer sk_live_e45de44e3028fd6bb13e24aaf69b9fce74babe53',
            'Content-Type' => 'application/json',
        ])->get($url)->json();

        if ($data['status'] == false) {
            return false;
        } else {
            return $data;
        }
    }

    public static function initiateTransfer($amount, $recipient, $reason, $reference_id)
    {
        $url = "https://api.paystack.co/transfer";

        $field = [
            'source' => 'balance',
            'amount' => $amount * 100,
            'recipient' => $recipient,
            'reason' => $reason,
            'reference' => $reference_id,
        ];

        $data = Http::withHeaders([
            'Accept' => 'application/json',
            // 'Authorization' => 'Bearer sk_test_b4a6648ab7cf38b99521ca9e65c2f12cf7da11ae',
            'Authorization' => 'Bearer sk_live_e45de44e3028fd6bb13e24aaf69b9fce74babe53',
            'Content-Type' => 'application/json',
        ])->post($url, $field)->json();

        if ($data) {
            if ($data['status'] == true) {
                return $data;
            }
        }

        return false;
    }

    public static function verifyTransfer($reference_id)
    {
        $url = "https://api.paystack.co/transfer/verify/$reference_id";

        $field = [
            'reference' => $reference_id,
        ];

        $data = Http::withHeaders([
            'Accept' => 'application/json',
            // 'Authorization' => 'Bearer sk_test_b4a6648ab7cf38b99521ca9e65c2f12cf7da11ae',
            'Authorization' => 'Bearer sk_live_e45de44e3028fd6bb13e24aaf69b9fce74babe53',
            'Content-Type' => 'application/json',
        ])->get($url, $field)->json();

        if ($data) {
            if ($data['status'] == false) {
                return false;
            }
            return true;
        }

        return false;

    }

    public static function chargeCard($email,$amount,$auth_code)
    {
        return '';
        $url = "https://api.paystack.co/transaction/charge_authorization";
        $field = [
            'email' => $email,
            'amount' => $amount * 100,
            "authorization_code"=> $auth_code
        ];

        $data = Http::withHeaders([
            'Accept' => 'application/json',
            // 'Authorization' => 'Bearer sk_test_b4a6648ab7cf38b99521ca9e65c2f12cf7da11ae',
            'Authorization' => 'Bearer sk_live_e45de44e3028fd6bb13e24aaf69b9fce74babe53',
            'Content-Type' => 'application/json',
        ])->post($url, $field)->json();

        return $data;
    }

    // TRF_oya0fbna98vcqwuw
}
