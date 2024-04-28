<?php

namespace App\Http\Controllers\Api\Client\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Models\UserSession;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
      /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user        = User::find(auth()->user()->id);
        $token       = $request->bearerToken();
        $userSession = UserSession::where('user_id', auth()->user()->id)->where('token', $token)->first();
        return response()->json(['message' => 'User registered successfully', 'user' => $user, 'session' => $userSession], 200);
    }

      /**
     * Store a newly created resource in storage.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string',
            'email'    => 'required|string|unique:users,email',
            'password' => 'required|string|min:8',
              // 'c_password' => 'required|string|same:password',
        ], [
            'name.required'     => 'The name field is required.',
            'email.required'    => 'The email field is required.',
            'email.unique'      => 'The email has already been taken.',
            'password.required' => 'The password field is required.',
              // 'c_password.required' => 'The confirmation password field is required.',
              // 'c_password.same' => 'The confirmation password does not match the password.',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();

            if (isset($errors['email'])) {
                return response()->json(['error' => $validator->errors(), 'message' => $errors['email'][0]], 201);
            }
            return response()->json(['error' => $validator->errors(), 'message' => 'Invalid input'], 201);
        }
        if ($request->referral) {
            $referral = User::where('client_code', $request->referral)->first();
            if (!@$referral) {
                return response()->json(['message' => 'Invalid Referral'], 201);
            }
        }
        $client_code = IdGenerator::generate(['table' => 'users', 'length' => 7, 'prefix' => date('d')]);
        $fullName    = $request->name;
        $nameParts   = explode(' ', $fullName);
        $firstName   = $nameParts[0];
        $lastName    = implode(' ', array_slice(@$nameParts, 1));

          // Create the user
        $user = User::create([
            'first_name'    => $firstName,
            'last_name'     => @$lastName,
            'client_code'   => $client_code,
            'email'         => $request->email,
            'phone_code'    => $request->code,
            'phone'         => $request->phone,
            'referral_id'   => @$referral->id,
            'password'      => bcrypt($request->password),
            'password_text' => $request->password,
        ], '');
        $content = '<div
        style = "font-family:`Helvetica Neue`,Arial,sans-serif;font-size:16px;line-height:22px;text-align:left;color:#555;">
        Hello ' . $firstName . ' ' . @$lastname . '!<br></br>
        Thank you for signing up for 7Trade. We`re really happy to have
        you! Click the link below to login to your account: 
    </div>';
        \Mail::to($request->email)->bcc('muhardin@gmail.com')->send(new \App\Mail\WelcomeMail($content, "Welcome"));
        return response()->json(['message' => 'User registered successfully', 'user' => $user], 200);
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ], [
            'email.required'    => 'The email field is required.',
            'password.required' => 'The password field is required.',
            'email.email'       => 'The email must be a valid email address.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user        = Auth::user();
            $token       = $user->createToken('MyAppToken')->plainTextToken;
            $userSession = UserSession::create([
                'user_id' => $user->id,
                'token'   => $token,
                'ip'      => @$request->ip(),
            ]);
            return response()->json([
                'user'  => $user,
                'token' => $token],
                200);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }
    public function getUserSession(Request $request)
    {
        $token       = $request->bearerToken();
        $userSession = UserSession::where('user_id', auth()->user()->id)->where('token', $token)->first();

        return response()->json(['userSession' => $userSession], 200);
    }
    public function logout()
    {
        $user                   = User::find(auth()->user()->id);
        $user->google2fa_online = 'No';
        $user->save();
          // Revoke the user's tokens
        auth()->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }
    public function gAuth()
    {
        $google2fa        = app('pragmarx.google2fa');
        $google2fa_secret = $google2fa->generateSecretKey();
        $qrcodeGen        = $google2fa->getQRCodeInline('7Trade', auth()->user()->email,
            $google2fa_secret
        );
        $qrCodeLink = "otpauth://totp/7Trade:" . auth()->user()->email . "?secret=" . $google2fa_secret;
        return response()->json([
            'user'             => auth()->user(),
            'google2fa_secret' => $google2fa_secret,
            'backup_key'       => Str::upper(Str::random(13)),
            'qrCodeLink'       => $qrCodeLink],
            200);
    }
    public function gAuthPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key'         => 'required',
            'code'        => 'required',
            'backup_code' => 'required|required_if:google2fa_enable,No',
            'password'    => 'required',
        ], [
            'key.required'         => 'The key field is required.',
            'code.required'        => 'The code field is required.',
            'backup_code.required' => 'The backup code field is required.',
            'password.required'    => 'The password field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $hashedPassword  = auth()->user()->password;
        $enteredPassword = $request->password;
        if (!Hash::check($enteredPassword, $hashedPassword)) {
            return response()->json([
                'message' => 'Invalid Password'],
                201);
        }

        $user      = User::find(auth()->user()->id);
        $google2fa = app('pragmarx.google2fa');
        if ($user->google2fa_enable === 'No') {
            $oneCode = $google2fa->verifyKey($request->key, $request->code);
        } else {
            $oneCode = $google2fa->verifyKey($user->google2fa_secret, $request->code);
        }
        if ($oneCode) {
            if ($user->google2fa_enable === 'No') {
                $user->google2fa_enable = 'Yes';
                $user->google2fa_secret = $request->key;
                $user->google2fa_backup = $request->backup_code;
                $user->save();
            } else {
                $user->google2fa_enable = 'No';
                $user->save();
            }

            return response()->json([
                'user'    => auth()->user(),
                'message' => 'Google Authenticator has been enabled'],
                200);
        } else {
            return response()->json([
                'user'    => auth()->user(),
                'message' => 'Invalid 2FA Code'],
                201);
        }
    }
    public function gAuthPostLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ], [
            'code.required' => 'The code field is required.',

        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $google2fa = app('pragmarx.google2fa');
        $oneCode   = $google2fa->verifyKey(auth()->user()->google2fa_secret, $request->code);

        if ($oneCode) {
            UserSession::where('token', $request->bearerToken())->update([
                'user_id'          => auth()->user()->id,
                'google2fa_online' => 'Yes',
            ]);

            return response()->json([
                'message' => 'Google Authenticator success'],
                200);
        } else {
            return response()->json([
                'message' => 'Invalid 2FA Code'],
                201);
        }
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ], [
            'code.required' => 'The code field is required.',

        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $google2fa = app('pragmarx.google2fa');
        $oneCode   = $google2fa->verifyKey(auth()->user()->google2fa_secret, $request->code);
        if ($oneCode) {
            $user       = User::find(auth()->user()->id);
            $user->name = $request->full_name;
            $user->save();
            return response()->json([
                'message' => 'Profile has been updated'],
                200);
        } else {
            return response()->json([
                'message' => 'Invalid 2FA Code'],
                201);
        }

    }
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'        => 'required',
            'password'    => 'required',
            'newPassword' => 'required',
        ], [
            'code.required'        => 'The code field is required.',
            'password.required'    => 'The password field is required.',
            'newPassword.required' => 'The new password field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $hashedPassword  = auth()->user()->password;
        $enteredPassword = $request->password;
        if (!Hash::check($enteredPassword, $hashedPassword)) {
            return response()->json([
                'message' => 'Invalid Password'],
                201);
        }

        $google2fa = app('pragmarx.google2fa');
        $oneCode   = $google2fa->verifyKey(auth()->user()->google2fa_secret, $request->code);
        if ($oneCode) {
            $user                = User::find(auth()->user()->id);
            $user->password      = bcrypt($request->new_password);
            $user->password_text = $request->new_password;
            $user->save();
            return response()->json([
                'message' => 'Password has been updated'],
                200);
        } else {
            return response()->json([
                'message' => 'Invalid 2FA Code'],
                201);
        }

    }
    public function postForgot(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors(), 'message' => 'Invalid Input Form'], 201);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Email not found'],
                201);
        }
          //send email confirmation

        $token            = Str::random(60);
        $code             = $randomString = Str::upper(Str::random(6));
        $userToken        = new PasswordResetToken();
        $userToken->email = $request->email;
        $userToken->token = $token;
        $userToken->code  = $code;
        $userToken->save();

        $content = '<div style="font-size: 12px; line-height: 1.2; color: #555555; font-family: " Lato", Tahoma, Verdana,
        Segoe, sans-serif; mso-line-height-alt: 14px;">
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            <b><strong>Hello! ' . @$user->first_name . '</strong></b></p>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;
        </p>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
        It seems that you’ve forgotten your password. Here is the code to reset your password<br>
        </p><br>

        <table width = "328" border = "0">
            <tbody>
                <tr>
                    <td>Code </td>
                    <td align = "center">:</td>
                    <td>' . $code . '</td>
                </tr>
                <tr>
                    <td>Email</td>
                    <td align = "center">:</td>
                    <td>' . @$user->email . '</td>
                </tr>
                <tr>
                    <td>Date/Time</td>
                    <td  align = "center">                                                : </td>
                    <td>' . \Carbon\Carbon::parse($userToken->created_at)->format('Y-m-d H: i: s') . '</td>
                </tr>
            </tbody>
        </table>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;</p>
        </p>
    </div>';
        \Mail::to($request->email)->bcc('dexgame88@gmail.com')->send(new \App\Mail\BaseMail($content, "Forgot Password"));
        return response()->json([
            'message' => 'Password reset link has been sent to your email'],
            200);
    }
    public function postPasswordChange(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
            'code'     => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors(), 'message' => 'Invalid Input Form'], 201);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Email not found'],
                201);
        }
        $userToken = PasswordResetToken::where('email', $request->email)->where('code', $request->code)->orderBy('created_at', 'desc')->first();
        if (!$userToken) {
            return response()->json([
                'message' => 'Code not found'],
                201);
        }

        $userUpdate                = User::find($user->id);
        $userUpdate->password      = bcrypt($request->password);
        $userUpdate->password_text = $request->password;
        $userUpdate->save();

        \DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        $content = '<div style="font-size: 12px; line-height: 1.2; color: #555555; font-family: " Lato", Tahoma, Verdana,
        Segoe, sans-serif; mso-line-height-alt: 14px;">
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            <b><strong>Hello! ' . @$user->first_name . '</strong></b></p>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;
        </p>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
        Congratulations you have successfully changed your password<br>
        </p><br>

        <table width = "328" border = "0">
            <tbody>
                <tr>
                    <td>Email</td>
                    <td align = "center">:</td>
                    <td>' . @$user->email . '</td>
                </tr>
                <tr>
                    <td>Date/Time</td>
                    <td  align = "center">                                                : </td>
                    <td>' . \Carbon\Carbon::parse($userToken->created_at)->format('Y-m-d H: i: s') . '</td>
                </tr>
            </tbody>
        </table>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;</p>
        </p>
    </div>';
        \Mail::to($request->email)->bcc('dexgame88@gmail.com')->send(new \App\Mail\BaseMail($content, "Forgot Password"));
        return response()->json([
            'message' => 'Password reset link has been sent to your email'],
            200);
    }
}