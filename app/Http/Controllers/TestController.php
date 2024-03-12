<?php

namespace App\Http\Controllers;

use App\Models\CryptoPrice;
use App\Models\StreakChallenge;
use App\Models\User;
use App\Models\UserStreak;
use App\Models\UserTrading;
use App\Services\SocketIOService;
use Carbon\Carbon;
use ElephantIO\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Ratchet\Client\WebSocket;

class TestController extends Controller
{
    protected $socketIOService;

    public function __construct(SocketIOService $socketIOService)
    {
        $this->socketIOService = $socketIOService;
    }
    public function emitEvent(Request $request)
    {
        $url = 'http://localhost:3009';

          // if client option is omitted then it will use latest client available,
          // aka. version 4.x
        $options = ['client' => Client::CLIENT_4X];

        $client = Client::create($url, $options);
        $client->connect();
        $client->of('/');  // can be omitted if connecting to default namespace

          // emit an event to the server
        $data = ['userId' => '1', 'amount' => 5000];
        $client->emit('push-notification', $data);

          // wait an event to arrive
          // beware when waiting for response from server, the script may be killed if
          // PHP max_execution_time is reached
          // if ($packet = $client->wait('message')) {
          //     // an event has been received, the result will be a \ElephantIO\Engine\Packet class
          //     // data property contains the first argument
          //     // args property contains array of arguments, [$data, ...]
          //     $data = $packet->data;
          //     $args = $packet->args;
          //     // access data
          //     $email = $data['email'];
          // }

          // end session
        $client->disconnect();

    }
    public function emitEventPost(Request $request)
    {
          // Replace this URL with the URL of your WebSocket server
        $serverUrl = 'ws://localhost:3009';

          // Connect to the WebSocket server
        $connector = new Connector();
        $connector($serverUrl)->then(function (WebSocket $conn) use ($request) {
              // Emit an event with the data from the request
            $conn->send(json_encode([
                'event' => $request->input('event'),
                'data'  => $request->input('data'),
            ]));
            $conn->close();
        }, function (\Exception $e) {
              // Handle connection error
            return response()->json(['error' => 'Could not connect to WebSocket server'], 500);
        });

          // Return success response
        return response()->json(['message' => 'Event emitted to WebSocket server'], 200);
    }
    public function index()
    {
        dd('_');
        $getStreak      = StreakChallenge::sum('amount');
        $streakCount    = 0;
        $streakCountNo  = 0;
        $streakCount9No = 0;

        $maxStreak       = 0;
        $count9Streak    = 0;
        $streakTarget    = 9;
        $streakCountYes9 = 0;

        $users = User::where('id', 1)->get();
        foreach ($users as $user) {
              // Get yesterday's date
            $yesterday = Carbon::yesterday();
            $data      = UserTrading::where('user_id', $user->id)
                ->whereDate('created_at', '<=', $yesterday)
                ->where('is_streak', 0)
                ->orderBy('id', 'desc')
                ->get();
            foreach ($data as $row) {
                if ($row->is_profit == 'Yes') {
                    $streakCount++;
                    if ($streakCount >= 3) {
                        $streakCountYes9++;
                        $streakCount = 0;
                    }
                } else {
                    $streakCount = 0;
                }

                if ($row->is_profit == 'No') {
                    $streakCountNo++;
                    if ($streakCountNo >= 3) {
                        $streakCount9No++;
                        $streakCountNo = 0;
                    }
                } else {
                    $streakCountNo = 0;
                }

            }

            if ($streakCountYes9 >= 0) {
                for ($i = 0; $i <= $streakCountYes9; $i++) {
                    $userStreak                = new UserStreak();
                    $userStreak->user_id       = $user->id;
                    $userStreak->streak_amount = $getStreak;
                    $userStreak->amount        = (0.1 / 100) * $getStreak;
                      // $userStreak->save();
                }
            }
            if ($streakCount9No >= 0) {
                for ($i = 0; $i <= $streakCount9No; $i++) {
                    $userStreak                = new UserStreak();
                    $userStreak->user_id       = $user->id;
                    $userStreak->streak_amount = $getStreak;
                    $userStreak->amount        = (0.1 / 100) * $getStreak;
                      // $userStreak->save();
                }
            }

            UserTrading::where('user_id', $user->id)
                ->whereDate('created_at', '<=', $yesterday)->update(['is_streak' => 1]);
        }

        dd($streakLength);
        $streakCount = 0;
        $maxStreak   = 0;

        $data = UserTrading::orderBy('id', 'desc')->get();
        foreach ($data as $row) {
            if ($row->is_profit === 'Yes') {
                $streakCount++;
                $maxStreak = max($streakCount, $maxStreak);
            } else {
                $streakCount = 0;
            }
        }
        if ($maxStreak >= 9) {

        }
        dd($maxStreak . ' | ' . $streakCount);
        return $maxStreak;
        $rowsToDelete = CryptoPrice::orderByDesc('id')->first();
        $rowSCount    = $rowsToDelete->id - 60;
        dd($rowSCount);
        $rowsToDelete = CryptoPrice::orderByDesc('id')->take(60)->get();
        dd($rowsToDelete);
        $checkLastPrev = CryptoPrice::orderBy('id', 'desc')->skip(1)->first();
        $checkLast     = CryptoPrice::orderBy('id', 'desc')->first();
        dd($checkLast);
        $user = User::with(['Referral'])->find(1);
        dd($user->Referral->email);
        $coin    = 'USDT';
        $network = 'bsc-bep20';
        $address = '0xbc92c9969776864415295b3868fe9204ec4339ef';
        $txId    = '1';
          // $data = AlphaCheckWd($txId);
          // $data = AlphaCreateWd($coin, $network, $address, 0.01);
          // $data = AlphaGetBalance();
          // $data = AlphaCheckTransactions($coin, $network);
          // $data = AlphaCreateAddress($coin, $network);
        $data = AlphaCheckAddress($coin, $network, $address);
        dd($data);

        $name          = 'Dex';
        $username      = 'Dex';
        $sendAmount    = '350000';
        $cost          = '250000';
        $receiveAmount = '25000';
        $created_at    = '2222';
        $email         = 'dexgame88@gmail.com';

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
        $apiKey    = '1899274';
        $apiSecret = '3a93231125a842528ab877223614e7e8';
        $paymentId = 'your_payment_id';

          /** create dedicated address */
        $url = "https://api.bucksbus.com/int/dedicate";

        $data = array(
            "asset_id"      => "BTC",
            "payer_email"   => "muhardin@gmail.com",
            "payer_name"    => "Hardin",
            "payer_lang"    => "en",
            "description"   => "The payment for item in store",
            "address_alloc" => "NEW",
            "custom"        => json_encode(array(
                "client_id" => "9a33c8b0-151c-481d-aef9-da15d883dc42",
                "org_id"    => 3,
                "field1"    => "341ba962-6ebf-4f3a-aef9-41c835a1dc26",
            )),
            "custom1"     => "some value 1",
            "custom2"     => "some value 2",
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
        $error    = curl_error($curl);

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
        $error    = curl_error($curl);

        curl_close($curl);

        if ($error) {
            echo "Error: $error";
        } else {
            echo "Response: $response";
        }
        dd($response);
        $fullName  = 'Siti Nadira';
        $nameParts = explode(' ', $fullName);
        $firstName = $nameParts[0];
        $lastName  = implode(' ', array_slice(@$nameParts, 1));
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
