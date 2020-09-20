<?php 
use App\SMS;

if (!function_exists('send_sms')) {
    
    function send_sms($phone, $message)
    {
        $url = "http://api.smsgh.com/v3/messages/send?" . "From=Solushop-GH" . "&To=%2B" . urlencode($phone) . "&Content=" . urlencode($message) . "&ClientId=dylsfikt" . "&ClientSecret=rrllqthk" . "&RegisteredDelivery=true";
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);

        $sms = new SMS;
        $sms->sms_message = $message;
        $sms->sms_phone = $phone;
        $sms->sms_state = 2;
        $sms->save();
    }
}