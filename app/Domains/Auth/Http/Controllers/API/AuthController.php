<?php

namespace App\Domains\Auth\Http\Controllers\API;

use App\Domains\Auth\Models\User;
use App\Domains\Auth\Http\Controllers\API\Auth;
use App\Domains\Auth\Rules\UniqueHash;
use App\Domains\Auth\Rules\UniqueMobileHash;
use App\Domains\Auth\Services\UserService;
use App\Http\Controllers\Controller;

// use Authy\AuthyApi;
use Cache;
use DB;
use Exception;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Str;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            ['mobile' => ['required', new UniqueMobileHash('users')]]
        );
        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        // $authyUserId = $authyApi->registerUser(
        //     '',
        //     $data['mobile'],
        //     app()->environment('production')
        //         ? '+1'
        //         : '+91'
        // )->id();

        // $user = $this->userService->registerUser(array_merge($data, [
        //     'authy_id' => $authyUserId
        // ]));

        $data = [
            'mobile' => $request->mobile
        ];

        $user = $this->userService->registerUser($data);

        $user->sendOTP();

        return response()->json(['message' => 'OTP sent on you mobile number.']);
    }

    public function finalRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', new UniqueMobileHash('users')],
            'email' => ['required', new UniqueHash('users')],
            'first_name' => ['required'],
            'last_name' => ['required'],
            'password' => ['required'],
            'username' => ['nullable'],
            'token' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $username = strtolower($request->first_name . $request->last_name) . substr($request->mobile, 6, 4);

        $data = [
            'mobile' => $request->mobile,
            'email' => $request->email,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'password' => $request->password,
            'username' => $username,
            'fcm_name' => $request->fcm_name,
        ];
        $user = User::where('mobile_hash', hash_sha($request->mobile))->where('mobile_verified_at', '!=', null)->first();
        $this->userService->update($user, $data);
        $message = array('title' => "Sign Up", 'body' => "Welcome to GiftPass!");
        // $this->notify(array($message,"Fcm_token_testing"));
        $mailData = [
            'subject' => 'registration successful',
            'title' => 'registration',
            'body' => "Hello" . auth()->user()->username . "! Thank you for signing up with GiftPass, the market place for all your gift card needs. Like us, you probably know a good deal when you see one! shop our market place and buy discounted giftcards, or buy full value gift cards direct from hundreds of retailers. Sell unwanted gift cards to earn that extra bit of cash! Trade a gift card you won't use for one that you will! And don't forget to Digitize you gift cards to have them handy and never misplace a gift card again!",
        ];
        Event::dispatch(new SendMail(auth()->user()->id, $mailData));
        return response()->json([
            'username' => $username,
            'name' => $user->first_name,
            'wallet_balance' => $user->balance,
            'message' => 'User registration successful.',
            'access_token' => $user->createToken('authToken')->accessToken,
        ]);
    }

    public function otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', new UniqueMobileHash('users')],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('mobile_hash', hash_sha($request->mobile))->first();

        if ($user) {
            $user->sendOTP();

            return response()->json(['message' => 'OTP sent']);
        }

        return response()->json(['message' => 'Invalid mobile number'], 401);
    }

    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', new UniqueMobileHash('users')],
            'otp' => ['required', 'numeric', 'digits_between:4,10'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('mobile_hash', hash_sha($request->mobile))->withoutGlobalScope('verified')->first();
        if ($user) {
            // if (Hash::check($request->otp, $user->otp)) {
            if ($user->verifyOTP($request->otp)) {
                $user->update(array('mobile_verified_at' => now()));
                return response()->json(['message' => 'OTP verified successfully', 'mobile' => $request->mobile], 200);
            } else {
                return response()->json(['message' => 'OTP verification failed'], 401);
            }
            // if ($user->verifyOTP($data['otp'])) {
            //     $user->update(array('mobile_verified_at' => now()));

            //     return response()->json(['message' => 'OTP verified successfully', 'mobile' => $data['mobile']], 200);
            // }
        }

        return response()->json(['message' => 'OTP verification failed'], 401);
    }

    public function login(Request $request)
    {

        $validator =  Validator::make($request->all(), [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
            'fcm_token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('email_hash', hash_sha($request->email))
            ->orWhere('username_hash', hash_sha($request->email))
            ->orWhere('mobile_hash', hash_sha($request->email))->first();


        // if ($user && Hash::check($request->password, $user->password)) {
        if ($user && (hash_sha($request->password) === $user->password)) {

            DB::table('fcm_token')->insert([
                ['fcm_token' => $request->fcm_token, 'user_id' => $request->user_id],

            ]);
            return response()->json([
                'name' => $user->first_name,
                'wallet_balance' => $user->balance,
                'message' => 'Login successfully',
                'access_token' => $user->getToken(),
            ]);
        } else {
            return response()->json([
                'message' => 'Wrong credentials.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function forgotPasswordSendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required'],
            // 'email' => ['required_without:mobile', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => implode(", ", $validator->messages()->all()),
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY
            ]);
        }

        $user = User::where('mobile_hash', hash_sha($request->mobile ?? null))
            ->orWhere('email', hash_sha($request->email ?? null))
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account exist with provided details.',
            ], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'mobile' => $request->mobile
        ];

        if (array_key_exists('mobile', $data)) {
            $user->sendOtp();
        } else {
            // Send an email with otp
        }

        return response()->json([
            'message' => sprintf(
                '%s with one time password has been sent on your %s.',
                array_key_exists('mobile', $data) ? 'A SMS' : 'An email',
                array_key_exists('mobile', $data) ? 'mobile number' : 'email address'
            ),
        ]);
    }

    public function forgotPasswordVerifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required'],
            // 'email' => ['required_without:mobile', 'email'],
            'otp' => ['required', 'numeric', 'digits_between:4,10'],
            // 'password' => ['required', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => implode(", ", $validator->messages()->all()),
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY
            ]);
        }

        $user = User::where('mobile_hash', hash_sha($request->mobile))
            ->orWhere('email', hash_sha($request->email))
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account exist with provided details.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$user->verifyOTP($request->otp)) {
            // if (! Hash::check($validatedData['otp'], $user->otp)) {
            return response()->json([
                'message' => 'Invalid OTP.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $token = Str::random(6);

        Cache::put($token, json_encode(['user_id' => $user->id]));

        return response()->json([
            'message' => 'Verification successful',
            'token' => $token
        ]);

        // DB::beginTransaction();

        // try {
        //     $user->update(['password' => $validatedData['password']]);
        // } catch (Exception $e) {
        //     DB::rollBack();

        //     return response()->json([
        //         'message' => 'There was an error while updating your password, please try again later.',
        //     ], Response::HTTP_INTERNAL_SERVER_ERROR);
        // }

        // return response()->json([
        //     'message' => 'Password updated successfully, please re-login with your new password.',
        // ]);

        // DB::commit();
    }

    public function forgotPasswordUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'password' => ['required', 'string']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => implode(", ", $validator->messages()->all()),
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY
            ]);
        }

        $data = Cache::pull($request->token);

        if (!$data) {
            return response()->json([
                'message' => 'Token is invalid'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::findOrFail(json_decode($data, true)['user_id']);

        DB::beginTransaction();

        try {
            $user->update(['password' => $request->password]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'There was an error while updating your password, please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        DB::commit();

        $mailData = [
            'subject' => 'Gifting a gift card',
            'title' => 'Gifting a gift card',
            'body' => "Your GiftPass password has been changed successfully. Please use your new passwork to login to GiftPass on all your devices.",
        ];
        // Mail::to($email)->send(new welcomeMail($mailData));
        Event::dispatch(new SendMail(auth()->user, $mailData));

        return response()->json([
            'message' => 'Password updated successfully, please re-login with your new password.',
        ]);
    }

    // withdraw cash api's


    public function logout(Request $request)
    {
        $user = Auth::user()->token();
        $user->revoke();
        // $user = User::where('id', auth()->user()->id)->first();
        // $user->update(['fcm_token' => '$request->password']);

        DB::table('fcm_token')->whereIn('user_id', auth()->user()->id)->delete();
        return response()->json([
            'message' => 'Logout successful',

        ]);
    }
    public function withdrawCashSendOTP(Request $request)
    {

        $user = User::where('id', auth()->user()->id)->first();
        if (!$user) {
            return response()->json([
                'message' => 'No account exist with provided details.',
            ], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'mobile' => $user->mobile
        ];

        if (array_key_exists('mobile', $data)) {
            $user->sendOtp();
        } else {
            // Send an email with otp
        }

        return response()->json([
            'message' => sprintf(
                '%s with one time password has been sent on your %s.',
                array_key_exists('mobile', $data) ? 'A SMS' : 'An email',
                array_key_exists('mobile', $data) ? 'mobile number' : 'email address'
            ),
        ]);
    }

    public function withdrawVerifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => ['required', 'numeric', 'digits_between:4,10'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => implode(", ", $validator->messages()->all()),
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY
            ]);
        }

        $user = User::where('id', auth()->user()->id)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account exist with provided details.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$user->verifyOTP($request->otp)) {
            // if (! Hash::check($validatedData['otp'], $user->otp)) {
            return response()->json([
                'message' => 'Invalid OTP.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $token = Str::random(6);

        Cache::put($token, json_encode(['user_id' => $user->id]));

        return response()->json([
            'message' => 'Verification successful',
            'token' => $token
        ]);

        // DB::beginTransaction();

        // try {
        //     $user->update(['password' => $validatedData['password']]);
        // } catch (Exception $e) {
        //     DB::rollBack();

        //     return response()->json([
        //         'message' => 'There was an error while updating your password, please try again later.',
        //     ], Response::HTTP_INTERNAL_SERVER_ERROR);
        // }

        // return response()->json([
        //     'message' => 'Password updated successfully, please re-login with your new password.',
        // ]);

        // DB::commit();
    }

    public function withdrawCash(Request $request)
    {
    }
}
