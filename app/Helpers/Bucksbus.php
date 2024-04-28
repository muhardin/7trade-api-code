<?php
/**
API Key Name : 1899274
API Secret : 3a93231125a842528ab877223614e7e8

7trde :
key : 1011415
secret : ff39f9a889704d4a87d4d766e70df77a
 */
function BbCreateAddress()
{

    $apiKey = '1011415';
    $apiSecret = 'ff39f9a889704d4a87d4d766e70df77a';
    $paymentId = 'your_payment_id';

    /** create dedicated address */
    $url = "https://api.bucksbus.com/int/dedicate";

    $data = array(
        "asset_id" => "USDT.TRC20",
        "payer_email" => "muhardin@gmail.com",
        "payer_name" => "Hardin",
        "payer_lang" => "en",
        "description" => "The payment for item in store",
        "address_alloc" => "NEW",
        "custom" => json_encode(array(
            "client_id" => "9a33c8b0-151c-481d-aef9-da15d883dc42",
            "org_id" => 3,
            "field1" => "341ba962-6ebf-4f3a-aef9-41c835a1dc26",
        )),
        "custom1" => "some value 1",
        "custom2" => "some value 2",
        "webhook_url" => "https://yourserver.com/payment/bucksbus/webhook",
        "success_url" => "https://yoursite.com/payment/bucksbus/success",
    );

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode("$apiKey:$apiSecret"),
    ));

    $response = curl_exec($curl);
    $error = curl_error($curl);

    curl_close($curl);

    // if ($error) {
    //     echo "Error: $error";
    // } else {
    //     echo "Response: $response";
    // }
    return json_decode($response, true);

}
function BbGetAddressTransaction()
{
    // $apiKey = '5174417';
    // $apiSecret = '21a00facee4d407dba52da514d42efcb';
    $paymentId = 'your_payment_id';

    $apiKey = '1011415';
    $apiSecret = 'ff39f9a889704d4a87d4d766e70df77a';

// Prepare query parameters
    $queryParams = http_build_query(array(
        "status" => "COMPLETE",
        "asset_id" => "USDT.TRC20",
        "dedicate_id" => "5ace2689-d69e-4e39-9200-7339923d3c58",
    ));

// Append query parameters to the base URL
    $url = "https://api.bucksbus.com/int/payments?" . $queryParams;

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Authorization: Basic ' . base64_encode("$apiKey:$apiSecret"),
    ));

    $response = curl_exec($curl);
    $error = curl_error($curl);

    curl_close($curl);

    // if ($error) {
    //     echo "Error: $error";
    // } else {
    //     echo "Response: $response";
    // }

    return json_decode($response, true);

}

function BbWd()
{

    $apiKey = '5174417';
    $apiSecret = '21a00facee4d407dba52da514d42efcb';
    $paymentId = 'your_payment_id';

    // $apiKey = '1011415';
    // $apiSecret = 'ff39f9a889704d4a87d4d766e70df77a';

    /** create dedicated address */
    $url = "https://api.bucksbus.com/int/withdraw";

    $data = array(
        "amount" => 20,
        "asset_id" => "USD",
        "withdraw_asset_id" => "USDT.TRC20",
        "payer_email" => "name@email.com",
        "is_test" => false,
        "payer_name" => "Customer name",
        "address" => "TBEYVFbNdYCKif1mdbUSZN3xvs21sWgmsw",
        "description" => "The payment for item in store",
        "custom" => json_encode(array(
            "client_id" => "9a33c8b0-151c-481d-aef9-da15d883dc42",
            "org_id" => 3,
            "field1" => "341ba962-6ebf-4f3a-aef9-41c835a1dc26",
        )),
        "custom1" => "some value 1",
        "custom2" => "some value 2",
        "webhook_url" => "https://yourserver.com/payment/bucksbus/webhook",
        "auto_realloc" => "N",
        "timeout" => 60,
    );

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode("$apiKey:$apiSecret"),
    ));

    $response = curl_exec($curl);
    $error = curl_error($curl);

    curl_close($curl);

    // if ($error) {
    //     echo "Error: $error";
    // } else {
    //     echo "Response: $response";
    // }
    return json_decode($response, true);

}
