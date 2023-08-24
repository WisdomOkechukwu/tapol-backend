<?php
namespace App\services;

use Exception;
use SendGrid;
use SendGrid\Mail\Mail;

// require 'vendor/autoload.php';

class SendGridService
{
    public static function sendEmail($subject, $to, $name, $html)
    {
        $email = new Mail();
        $email->setFrom("info@tapolgroup.com","Tapol");
        $email->setSubject($subject);
        $email->addTo($to, $name);
        $email->addContent("text/html", $html);
        // return getenv('SENDGRID_APIKEY');
        $sendgrid = new SendGrid("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");
        // try {
        //     $response = $sendgrid->send($email);
        //     print $response->statusCode() . "\n";
        //     print_r($response->headers());
        //     print $response->body() . "\n";
        // } catch (Exception $e) {
        //     echo 'Caught exception: ' . $e->getMessage() . "\n";
        // }

        if($response = $sendgrid->send($email)){
            if($response->statusCode() == 202){
                    return true;
            }else{

                return false;
            }
        }else{
            return false;
        }
    }


}
