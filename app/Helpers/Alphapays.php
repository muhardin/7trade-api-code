<?php
function createAddress()
{
    $merchantId = '5txf8mc-aklnryz7-tr5gkq-b3gn';
    $secret_key = 'sst1707729946179068463615175197';
    $bodyData = [
        'currency' => 'USDT',
        'callbackURL' => 'https://jualanyuk.shop',
        'blockchainNetwork' => 'ethereum-erc20',
        'userId' => '5005024',
        'userName' => 'test5005024',
        'merchantTransactionId' => '5005024',
        'firstName' => 'John',
        'lastName' => 'Doe',
        'email' => 'test@test.com',
        'countryCode' => 'US',
        'phoneNumber' => '1231231234',
        'address' => 'test',
        'birthDate' => '1999-02-01',
        'IPAddress' => '167.114.115.146',
        'fiatCurrency' => 'USD',
    ];

    $body = json_encode($bodyData);
    $signature = hash_hmac('sha256', $body, $secret_key);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.alphapays.com/api/v2.0/deposit/create-address',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'X-Merchant-Identifier: ' . $merchantId,
            'X-Signature: ' . $signature,
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}