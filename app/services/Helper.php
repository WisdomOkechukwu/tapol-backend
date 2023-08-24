<?php
namespace App\services;

use Carbon\Carbon;
use App\Models\Transaction;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\DB;

class Helper
{

    public static function endsWith($string, $endString)
    {
        $len = strlen($endString);
        if ($len == 0) {
            return true;
        }
        return (substr($string, -$len) === $endString);
    }

    public static function startsWith($string, $startString)
    {
        $length = strlen($startString);
        return (substr($string, 0, $length) === $startString);
    }

    public static function valid_email($email)
    {
        $valid = filter_var($email, FILTER_VALIDATE_EMAIL);
        return $valid;
    }

    public static function randomString($length = 32, $numeric = false)
    {
        $random_string = "";
        while (strlen($random_string) < $length && $length > 0) {
            if ($numeric === false) {
                $randnum = mt_rand(0, 61);
                $random_string .= ($randnum < 10) ?
                chr($randnum + 48) : ($randnum < 36 ?
                    chr($randnum + 55) : $randnum + 61);
            } else {
                $randnum = mt_rand(0, 9);
                $random_string .= chr($randnum + 48);
            }
        }
        return $random_string;
    }

    public static function last_day($month = '', $year = '')
    {
        if (empty($month)) {
            $month = date('m');
        }

        if (empty($year)) {
            $year = date('Y');
        }

        $result = strtotime("{$year}-{$month}-01");
        $result = strtotime('-1 second', strtotime('+1 month', $result));

        return date('Y-m-d', $result);
    }

    public static function getBankCode($bankname)
    {
        $bankcode = "000";
        if ($bankname == "STANDARD CHARTERED BANK NIGERIA PLC") {
            $bankcode = "068";
        }

        if ($bankname == "DIAMOND BANK NIGERIA PLC") {
            $bankcode = "044";
        }

        if ($bankname == "FIRST CITY MONUMENT BANK PLC") {
            $bankcode = "214";
        }

        if ($bankname == "UNITY BANK PLC") {
            $bankcode = "215";
        }

        if ($bankname == "STANBIC - IBTC BANK PLC") {
            $bankcode = "221";
        }

        if ($bankname == "STERLING BANK PLC") {
            $bankcode = "232";
        }

        if ($bankname == "JAIZ BANK") {
            $bankcode = "301";
        }

        if ($bankname == "JAIZ BANK PLC") {
            $bankcode = "301";
        }

        if ($bankname == "ACCESS BANK NIGERIA PLC") {
            $bankcode = "044";
        }

        if ($bankname == "ECOBANK NIGERIA PLC") {
            $bankcode = "050";
        }

        if ($bankname == "FIDELITY BANK PLC") {
            $bankcode = "070";
        }

        if ($bankname == "FIRST BANK OF NIGERIA PLC") {
            $bankcode = "011";
        }

        if ($bankname == "GUARANTY TRUST BANK PLC") {
            $bankcode = "058";
        }

        if ($bankname == "HERITAGE BANK") {
            $bankcode = "030";
        }

        if ($bankname == "KEYSTONE BANK PLC") {
            $bankcode = "082";
        }

        if ($bankname == "SKYE BANK PLC") {
            $bankcode = "076";
        }

        if ($bankname == "UNION BANK OF NIGERIA PLC") {
            $bankcode = "032";
        }

        if ($bankname == "UNITED BANK FOR AFRICA PLC") {
            $bankcode = "033";
        }

        if ($bankname == "WEMA BANK PLC") {
            $bankcode = "035";
        }

        if ($bankname == "ZENITH BANK PLC") {
            $bankcode = "057";
        }
        if ($bankname == "NPF MICROFINANCE BANK LIMITED") {
            $bankcode = "070001";
        }
        if ($bankname == "FIRST ROYAL MICROFINANCE BANK LIMITED") {
            $bankcode = "090164";
        }
        if ($bankname == "NAGARTA MICROFINANCE BANK LIMITED") {
            $bankcode = "090152";
        }
        if ($bankname == "ASO SAVINGS AND LOANS LTD") {
            $bankcode = "401";
        }
        if ($bankname == "OGIGE MICROFINANCE BANK LIMITED") {
            $bankcode = "530";
        }
        if ($bankname == "MICROCRED MICROFINANCE BANK LIMITED") {
            $bankcode = "271";
        }
        if ($bankname == "URBAN MICROFINANCE BANK LIMITED") {
            $bankcode = "344";
        }
        if ($bankname == "GOLDEN FUNDS MICROFINANCE BANK LIMITED") {
            $bankcode = "338";
        }
        if ($bankname == "CHIDERA MICROFINANCE BANK LIMITED") {
            $bankcode = "339";
        }
        if ($bankname == "OHHA MICROFINANCE BANK LIMITED") {
            $bankcode = "340";
        }

        return $bankcode;
    }

    public static function getBankName($preferred_bankname)
    {
        if ($preferred_bankname == '044') {
            return "Access Bank Plc";
        }

        if ($preferred_bankname == '023') {
            return "Citi Bank";
        }

        if ($preferred_bankname == '063') {
            return "Diamond Bank Plc";
        }

        if ($preferred_bankname == '050') {
            return "Ecobank Plc";
        }

        if ($preferred_bankname == '070') {
            return "Fidelity Bank Plc";
        }

        if ($preferred_bankname == '011') {
            return "First Bank of Nigeria PLC";
        }

        if ($preferred_bankname == '214') {
            return "First City Monument Bank PLC";
        }

        if ($preferred_bankname == '058') {
            return "Guaranty Trust Bank PLC";
        }

        if ($preferred_bankname == '030') {
            return "Heritage Bank";
        }

        if ($preferred_bankname == '301') {
            return "Jaiz Bank";
        }

        if ($preferred_bankname == '082') {
            return "Keystone Bank";
        }

        if ($preferred_bankname == '076') {
            return "Skye Bank PLC";
        }

        if ($preferred_bankname == '221') {
            return "Stanbic IBTC Bank PLC";
        }

        if ($preferred_bankname == '232') {
            return "Sterling Bank PLC";
        }

        if ($preferred_bankname == '100') {
            return "SUNTRUST BANK";
        }

        if ($preferred_bankname == '032') {
            return "Union Bank of Nigeria PLC";
        }

        if ($preferred_bankname == '033') {
            return "United Bank for Africa PLC";
        }

        if ($preferred_bankname == '215') {
            return "Unity Bank PLC";
        }

        if ($preferred_bankname == '035') {
            return "Wema Bank PLC";
        }

        if ($preferred_bankname == '057') {
            return "Zenith Bank PLC";
        }
        if ($preferred_bankname == '068') {
            return "Standard Chatered Bank Nigeria PLC";
        }
        if ($preferred_bankname == "530") {
            return $bankname = "OGIGE MICROFINANCE BANK LIMITED";
        }
        if ($preferred_bankname == "271") {
            return $bankname = "MICROCRED MICROFINANCE BANK LIMITED";
        }
        if ($preferred_bankname == "344") {
            return $bankname = "URBAN MICROFINANCE BANK LIMITED";
        }
        if ($preferred_bankname == "338") {
            return $bankname = "GOLDEN FUNDS MICROFINANCE BANK LIMITED";
        }
        if ($preferred_bankname == "339") {
            return $bankname = "CHIDERA MICROFINANCE BANK LIMITED";
        }
        if ($preferred_bankname == "340") {
            return $bankname = "OHHA MICROFINANCE BANK LIMITED";
        }

    }

    public static function getResponseMessage($code, $message, $status)
    {
        return response()->json(['status' => $status, 'message' => $message], $code);
    }

    public static function storeTransaction($customer_id, $message, $amount, $type = null)
    {
        $transaction = new Transaction();
        $transaction->customer_id = $customer_id;
        $transaction->message = $message;
        $transaction->type = $type;
        $transaction->amount = $amount;
        $transaction->save();
    }

    public static function generateRandomString($length = 25)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function getVerificationCode()
    {
        return $code = VerificationCode::inRandomOrder()->first()->code;
    }
//10-20 one uppercase, one lowercase, one spac char, one number
    public static function generatePassword($length)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;\':",./<>?';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public static function validatePassword($password, $minLength = 10, $maxLength = 20)
    {
        // $length = strlen($password);
        // if ($length < $minLength || $length > $maxLength) {
        //     return ['status'=>'error','message'=>'Password must contain a minimum of 10 characters and maximum of 20 characters'];
        // }
        if (!preg_match('/[a-z]/', $password)) {
            return ['status' => 'error', 'message' => 'Password must contain atleast a small letter'];
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return ['status' => 'error', 'message' => 'Password must contain atleast a capital letter'];
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            return ['status' => 'error', 'message' => 'Password must contain atleast a character'];
        }
        if (!preg_match('/[0-9]/', $password)) {
            return ['status' => 'error', 'message' => 'Password must contain atleast a number'];
        }

        return ['status' => 'success', 'message' => 'successful'];
    }

    public static function updateUpdatedAtColumn()
    {
        $customer = auth('customers')->user();
        DB::table('customers')
            ->where('customer_id', $customer->customer_id)
            ->update(['updated_at' => Carbon::now()]);
    }

}
