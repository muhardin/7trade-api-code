<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserCrypto;
use App\Models\Wallet;
use App\Models\Webhook;
use Illuminate\Http\Request;

class CallBackController extends Controller
{

    public function handle(Request $request)
    {
          // $data = $request->json()->all();
        $data         = $request->all();
        $request_type = $request->method();
        $dataToString = json_encode($data);

        $dataJsonDe = json_decode($dataToString);
        Webhook::create(['webhook' => $dataToString, 'type' => $request_type]);
        if ($dataJsonDe->transaction->status === 'completed') {
            $checkOwner = UserCrypto::where('crypto_address', $dataJsonDe->transaction->address)->where('user_id', $dataJsonDe->transaction->userID)->first();
            if ($checkOwner) {
                $hashCheck = Wallet::where('trx', $dataJsonDe->transaction->hash)->first();
                if (@!$hashCheck) {
                    $wallet                    = new Wallet();
                    $wallet->user_id           = $checkOwner->user_id;
                    $wallet->trx               = $dataJsonDe->transaction->hash;
                    $wallet->amount            = $dataJsonDe->transaction->amount;
                    $wallet->type              = 'In';
                    $wallet->confirmation_code = $dataJsonDe->transaction->processorTransactionId;
                    $wallet->description       = 'Deposit from your deposit address';
                    $wallet->save();
                }
            }
        }
        return response()->json([
            "Currency"               => $dataJsonDe->transaction->currency,
            "address"                => $dataJsonDe->transaction->address,
            "hash"                   => $dataJsonDe->transaction->hash,
            "amount"                 => $dataJsonDe->transaction->amount,
            "processorTransactionId" => $dataJsonDe->transaction->processorTransactionId,
            "ts"                     => $dataJsonDe->transaction->ts,
            "status"                 => $dataJsonDe->transaction->status,
            "userID"                 => $dataJsonDe->transaction->userID,
            "userName"               => $dataJsonDe->transaction->userName,
            "merchantTransactionId"  => $dataJsonDe->transaction->merchantTransactionId,
        ], 200);
    }

}