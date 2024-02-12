<?php

namespace App\Http\Controllers\Api\Client\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
    public function index()
    {
        $user = User::find(auth()->user()->id);
        return response()->json(['message' => 'User registered successfully', 'user' => $user], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|min:8',
            // 'c_password' => 'required|string|same:password',
        ], [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.unique' => 'The email has already been taken.',
            'password.required' => 'The password field is required.',
            // 'c_password.required' => 'The confirmation password field is required.',
            // 'c_password.same' => 'The confirmation password does not match the password.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $client_code = IdGenerator::generate(['table' => 'users', 'length' => 7, 'prefix' => date('y')]);
        //output: 2000000001,2000000002,2000000003
        //output: 2100000001,2100000002,2100000003

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'client_code' => $client_code,
            'email' => $request->email,
            'phone_code' => $request->code,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
        ], '');

        return response()->json(['message' => 'User registered successfully', 'user' => $user], 200);
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'The email field is required.',
            'password.required' => 'The password field is required.',
            'email.email' => 'The email must be a valid email address.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('MyAppToken')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token],
                200);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }
    public function logout()
    {
        $user = User::find(auth()->user()->id);
        $user->google2fa_online = 'No';
        $user->save();
        // Revoke the user's tokens
        // auth()->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }
    public function gAuth()
    {
        $google2fa = app('pragmarx.google2fa');
        $google2fa_secret = $google2fa->generateSecretKey();
        $qrcodeGen = $google2fa->getQRCodeInline('7Trade', auth()->user()->email,
            $google2fa_secret
        );
        $qrCodeLink = "otpauth://totp/7Trade:" . auth()->user()->email . "?secret=" . $google2fa_secret;
        return response()->json([
            'user' => auth()->user(),
            'google2fa_secret' => $google2fa_secret,
            'backup_key' => Str::upper(Str::random(13)),
            'qrCodeLink' => $qrCodeLink],
            200);
    }
    public function gAuthPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required',
            'code' => 'required',
            'backup_code' => 'required|required_if:google2fa_enable,No',
            'password' => 'required',
        ], [
            'key.required' => 'The key field is required.',
            'code.required' => 'The code field is required.',
            'backup_code.required' => 'The backup code field is required.',
            'password.required' => 'The password field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $hashedPassword = auth()->user()->password;
        $enteredPassword = $request->password;
        if (!Hash::check($enteredPassword, $hashedPassword)) {
            return response()->json([
                'message' => 'Invalid Password'],
                201);
        }

        $user = User::find(auth()->user()->id);
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
                'user' => auth()->user(),
                'message' => 'Google Authenticator has been enabled'],
                200);
        } else {
            return response()->json([
                'user' => auth()->user(),
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
        $oneCode = $google2fa->verifyKey(auth()->user()->google2fa_secret, $request->code);
        $user = User::find(auth()->user()->id);
        if ($oneCode) {
            $user->google2fa_online = "Yes";
            $user->save();
            return response()->json([
                'message' => 'Google Authenticator has been enabled'],
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
        $oneCode = $google2fa->verifyKey(auth()->user()->google2fa_secret, $request->code);
        if ($oneCode) {
            $user = User::find(auth()->user()->id);
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
            'code' => 'required',
            'password' => 'required',
            'newPassword' => 'required',
        ], [
            'code.required' => 'The code field is required.',
            'password.required' => 'The password field is required.',
            'newPassword.required' => 'The new password field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $hashedPassword = auth()->user()->password;
        $enteredPassword = $request->password;
        if (!Hash::check($enteredPassword, $hashedPassword)) {
            return response()->json([
                'message' => 'Invalid Password'],
                201);
        }

        $google2fa = app('pragmarx.google2fa');
        $oneCode = $google2fa->verifyKey(auth()->user()->google2fa_secret, $request->code);
        if ($oneCode) {
            $user = User::find(auth()->user()->id);
            $user->password = bcrypt($request->new_password);
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
}
