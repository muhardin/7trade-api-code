<?php

use App\Models\User;
function AlphaCreateAddress($coin, $network, $merchantId, $userId)
{
    /**
    $coin    = 'USDT';
    $network = 'ethereum-erc20';
     */
    $user = User::find($userId);
    if (@$user->last_name) {
        $lastName = $user->last_name;
    } else {
        $str = @$user->first_name;
        $lastName = substr($str, 0, 1);
    }
    $merchantId = '5txf8mc-aklnryz7-tr5gkq-b3gn';
    $secret_key = 'sst1707729946179068463615175197';

    $bodyData = [
        'currency' => $coin,
        'callbackURL' => 'https://7trade.pro/callback/alphapays',
        'blockchainNetwork' => $network,
        'userId' => $user->id,
        'userName' => $user->client_code,
        'merchantTransactionId' => $merchantId,
        'firstName' => @$user->first_name,
        'lastName' => @$lastName,
        'email' => @$user->email,
        'countryCode' => 'US',
        'phoneNumber' => @$user->phone_code . @$user->phone,
        'address' => 'No Address',
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
function AlphaCheckTransactions($coin, $network)
{
    $merchantId = '5txf8mc-aklnryz7-tr5gkq-b3gn';
    $secret_key = 'sst1707729946179068463615175197';
    $bodyData = [
        'currency' => $coin,
        'blockchainNetwork' => $network,
        'offset' => '0',
        'limit' => '2',

    ];

    $body = json_encode($bodyData);
    $signature = hash_hmac('sha256', $body, $secret_key);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.alphapays.com/api/v2.0/deposit/transactions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'X-Merchant-Identifier: ' . $merchantId;
    $headers[] = 'X-Signature: ' . $signature;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);

    curl_close($ch);
    return json_decode($result, false);
}
function AlphaCheckAddress($coin, $network, $address)
{
    $merchantId = '5txf8mc-aklnryz7-tr5gkq-b3gn';
    $secret_key = 'sst1707729946179068463615175197';
    $bodyData = [
        'currency' => $coin,
        'blockchainNetwork' => $network,
        'address' => $address,
        'offset' => '0',
        'limit' => '2',

    ];

    $body = json_encode($bodyData);
    $signature = hash_hmac('sha256', $body, $secret_key);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://api.alphapays.com/api/v2.0/deposit/transactions-by-address');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'X-Merchant-Identifier: ' . $merchantId;
    $headers[] = 'X-Signature: ' . $signature;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    return json_decode($result, false);
    // return json_decode('{
    //     "success": true,
    //     "transactions": [
    //       {
    //         "currency": "BTC",
    //         "address": "2MyJ7YbWMQVaA6rjyapDsMHHaMNvTRpQtcN",
    //         "hash": "d36485a59bd11dc908fd1f6174c3e0142ee751d80bb2ec3252ecbd560dae3b50",
    //         "amount": "150",
    //         "ts": 1679593199,
    //         "status": "completed",
    //         "processorTransactionId": "2454"
    //       }
    //     ],
    //     "offset": 0,
    //     "limit": "1"
    //   }', false);
}
function AlphaGetDepositTransaction($id)
{
    $merchantId = '5txf8mc-aklnryz7-tr5gkq-b3gn';
    $secret_key = 'sst1707729946179068463615175197';
    $bodyData = [
        'merchantTransactionId' => $id,
    ];

    $body = json_encode($bodyData);
    $signature = hash_hmac('sha256', $body, $secret_key);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://api.alphapays.com/api/v2.0/deposit/transaction-info');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'X-Merchant-Identifier: ' . $merchantId;
    $headers[] = 'X-Signature: ' . $signature;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    return json_decode($result, false);
}
function AlphaGetBalance()
{
    $merchantId = '5txf8mc-aklnryz7-tr5gkq-b3gn';
    $secret_key = 'sst1707729946179068463615175197';
    $bodyData = [

    ];

    $body = json_encode($bodyData);
    $signature = hash_hmac('sha256', $body, $secret_key);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://api.alphapays.com/api/v2.0/get-balances');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'X-Merchant-Identifier: ' . $merchantId;
    $headers[] = 'X-Signature: ' . $signature;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, false);
}
function AlphaCreateWd($coin, $network, $address, $amount, $orderId, $receiverId)
{
    $merchantId = '5txf8mc-aklnryz7-tr5gkq-b3gn';
    $secret_key = 'sst1707729946179068463615175197';
    $bodyData = [
        'currency' => $coin,
        'callbackURL' => 'https://domain.tld/callback?param1=val&param2=val',
        'blockchainNetwork' => $network,
        'confirmationNo' => '1',
        'merchantOrderId' => $orderId,
        'receivers' => [
            [
                'amount' => $amount,
                'address' => $address,
                'receiverId' => $receiverId,
            ],
        ],
    ];

    $body = json_encode($bodyData);
    $signature = hash_hmac('sha256', $body, $secret_key);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://api.alphapays.com/api/v2.0/withdrawal/create');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'X-Merchant-Identifier: ' . $merchantId;
    $headers[] = 'X-Signature: ' . $signature;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, false);
    // return json_decode('{
    //     "success": true,
    //     "txId": "pz-421781",
    //     "status": "processing"
    //   }', false);
}
function AlphaCheckWd($txId)
{
    $merchantId = '5txf8mc-aklnryz7-tr5gkq-b3gn';
    $secret_key = 'sst1707729946179068463615175197';
    $bodyData = [
        'txId' => $txId,
    ];

    $body = json_encode($bodyData);
    $signature = hash_hmac('sha256', $body, $secret_key);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://api.alphapays.com/api/v2.0/withdrawal/transaction-info');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'X-Merchant-Identifier: ' . $merchantId;
    $headers[] = 'X-Signature: ' . $signature;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, false);
    // return json_decode('{
    //     "success": true,
    //     "transaction": {
    //       "txId": "pz-421804",
    //       "currency": "BTC",
    //       "blockchainNetwork": "bitcoin",
    //       "hash": "056b04392ea2a62be8fe68b0fe1187a71d8086e1dc5bcb42526b6c234be22784",
    //       "confirmationNo": 3,
    //       "amount": 0.0003485,
    //       "ts": 1683655562,
    //       "status": "completed",
    //       "fiatAmount": "13.02",
    //       "fiatCurrency": "USD",
    //       "merchantOrderId": "11114"
    //     }
    //   }', false);

}
