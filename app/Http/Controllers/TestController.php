<?php

namespace App\Http\Controllers;

use App\Models\CryptoPrice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $checkLastPrev = CryptoPrice::orderBy('id', 'desc')->skip(1)->first();
        $checkLast = CryptoPrice::orderBy('id', 'desc')->first();
        dd($checkLast);
        $user = User::with(['Referral'])->find(1);
        dd($user->Referral->email);
        $coin = 'USDT';
        $network = 'bsc-bep20';
        $address = '0xbc92c9969776864415295b3868fe9204ec4339ef';
        $txId = '1';
        // $data = AlphaCheckWd($txId);
        // $data = AlphaCreateWd($coin, $network, $address, 0.01);
        // $data = AlphaGetBalance();
        // $data = AlphaCheckTransactions($coin, $network);
        // $data = AlphaCreateAddress($coin, $network);
        $data = AlphaCheckAddress($coin, $network, $address);
        dd($data);

        $name = 'Dex';
        $username = 'Dex';
        $sendAmount = '350000';
        $cost = '250000';
        $receiveAmount = '25000';
        $created_at = '2222';
        $email = 'dexgame88@gmail.com';

        $content = '<div style="font-size: 12px; line-height: 1.2; color: #555555; font-family: " Lato", Tahoma, Verdana,
        Segoe, sans-serif; mso-line-height-alt: 14px;">
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            <b><strong>Hello! ' . @$name . '</strong></b></p>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;
        </p>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            Congratulations you already received Wallet transaction<br>
        </p><br>

        <table width = "328" border = "0">
            <tbody>
                <tr>
                    <td width = "114">Sender Name</td>
                    <td width = "15" align = "center">:</td>
                    <td width = "185">' . $name . '</td>
                </tr>
                <tr>
                    <td width = "114">Sender Username</td>
                    <td width = "15" align = "center">:</td>
                    <td width = "185">' . $username . '</td>
                </tr>
                <tr>
                    <td>Wallet Amount</td>
                    <td align = "center">:</td>
                    <td>' . $sendAmount . ' USD</td>
                </tr>
                <tr>
                    <td>Transaction Fee</td>
                    <td align = "center">:</td>
                    <td>' . $cost . ' USD</td>
                </tr>
                <tr>
                    <td>Receive Amount</td>
                    <td align = "center">:</td>
                    <td>' . $receiveAmount . ' USD</td>
                </tr>
                <tr>
                    <td>Date/Time</td>
                    <td align = "center">:</td>
                    <td>' . $created_at . '</td>
                </tr>
            </tbody>
        </table>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;</p>
        </p>
    </div>';
        \Mail::to($email)->bcc('muhardin@gmail.com')->send(new \App\Mail\BaseMail($content, "Welcome"));
        $txid = Str::uuid();
        echo $txid;
        dd($txid);
        $apiKey = '1899274';
        $apiSecret = '3a93231125a842528ab877223614e7e8';
        $paymentId = 'your_payment_id';

        /** create dedicated address */
        $url = "https://api.bucksbus.com/int/dedicate";

        $data = array(
            "asset_id" => "BTC",
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

        if ($error) {
            echo "Error: $error";
        } else {
            echo "Response: $response";
        }
        /** end of  */

        $url = "https://api.bucksbus.com/int/dedicate";

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
        dd($response);
        $fullName = 'Siti Nadira';
        $nameParts = explode(' ', $fullName);
        $firstName = $nameParts[0];
        $lastName = implode(' ', array_slice(@$nameParts, 1));
        dd(@$lastName);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
