<?php
/**
API Key Name : 1899274
API Secret : 3a93231125a842528ab877223614e7e8

 */
function BbCreateAddress()
{
    $apiKey = '5174417';
    $apiSecret = 'debe4fbd97d144d886b93e964a5cebf2';
    $paymentId = 'your_payment_id';

    $url = "https://api.bucksbus.com/int/payment/{$paymentId}";

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, "$apiKey:$apiSecret");

    $response = curl_exec($curl);
    $error = curl_error($curl);

    curl_close($curl);

    if ($error) {
        echo "Error: $error";
    } else {
        echo "Response: $response";
    }
    return json_decode($response, true);
}