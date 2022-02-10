<?php

namespace App\Domains\Auth\Http\Controllers\API;

use App\Domains\Auth\Models\User;
use App\Http\Controllers\Controller;
use DB;
use Exception;
use Cache;
use Str;
use Illuminate\Http\Response;
use Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\App;
use App\Domains\Auth\Rules\UniqueHash;
use App\Domains\Auth\Rules\UniqueMobileHash;
use Illuminate\Support\Facades\Mail;
use App\Mail\emailUpdateVerification;

class UserController extends Controller
{
    public function getProfile()
    {
        /** @var User */
        $user = auth()->user();

        return response()->json([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => $user->email,
            'mobile' => $user->mobile
        ]);
    }

    public function updateProfile(Request $request)
    {
        /** @var User */
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'username' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $user->update(['first_name' => $request->first_name, 'last_name' => $request->last_name, 'username' => $request->username]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'message' => 'There was a error updating your profile, please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Profile updated successfully.',
        ]);
    }

    public function getMyDashBoard()
    {
        /** @var User */
        $user = auth()->user();
        $cards = $user->cards()->where('active', 1)->get();
        $card_total = 0;
        foreach ($cards as $key => $card) {
            $card_total = $card_total + floatval($card->value);
        }

        return response()->json([
            'name' => $user->first_name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'wallet_total' => '$ ' . $user->balance,
            'total_card_balance' => $card_total
        ]);
    }

    public function changeNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required',  new UniqueMobileHash('users', auth()->user()->id)],
            'email' => ['required', new UniqueHash('users', auth()->user()->id)],
        ]);

        if ($validator->fails()) {
            if(count($validator->messages()->all()) == 2){
                return response()->json(['change_type' => 'email', 'message' => 'The email has already been taken', 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
            } else{
                if($validator->messages()->all()[0] == 'The mobile has already been taken'){
                   $type = 'mobile';
                } else{
                    $type = 'email';
                }
                return response()->json(['change_type' => $type, 'message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
        }

        $data = $validator->validated();

        /** @var User */
        $user = auth()->user();

        $new_mobile = $request->mobile;
        $new_email = $request->email;
        if (($user->email != $new_email) && ($user->mobile != $new_mobile)) {
            $new_mobile = $request->mobile;
            $new_email = $request->email;
            $changeType = 'change_mobile_email';
        }
        elseif (($user->email == $new_email) && ($user->mobile != $new_mobile)) {
            $new_mobile = $request->mobile;
            $new_email = $request->email;
            $changeType = 'change_mobile';
        }
        elseif (($user->email != $new_email) && ($user->mobile == $new_mobile)) {
            $new_mobile = $request->mobile;
            $new_email = $request->email;
            $changeType = 'change_email';
        } else{
            return response()->json([
                'message' => 'Nothing to change'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $key = md5($user);
        if ((isset($new_mobile)) && (isset($new_email))) {
            Cache::put(
                $key . 'change_profile',
                json_encode([
                    'mobile' => $new_mobile,
                    'email' => $new_email,
                    'changeType' => $changeType
                ])
            );

            return response()->json([
                'message' => 'Verify your password'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'There was a problem updating mobile number. Please try again'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function passwordVerify(Request $request)
    {
        $validator =  Validator::make($request->all(), [
            'password' => ['required', 'string'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        /** @var User */
        $user = auth()->user();
        if ($user && (hash_sha($request->password) === $user->password)) {

            $key = md5($user);
            $token = Str::random(6);
            Cache::put(
                $key . 'token',
                json_encode([
                    'token' => $token,
                ])
            );
            $cached = Cache::get($key . 'change_profile');
            if ($cached) {
                $decoded = json_decode($cached, true);
                if (isset($decoded['changeType'])) {
                    if ($decoded['changeType'] == 'change_mobile_email') {
                        $otp = App::environment('production')
                            ? rand(100000, 999999)
                            : 123456;
                        if (App::environment('production'))  $user->sendOTP();

                        $email = $decoded['email'];

                        $mailData = [
                            'title' => 'Email Verification',
                            'body' => 'Please enter the below mentioned OTP for email verification.',
                            'otp' => $otp
                        ];

                        Mail::to($email)->send(new emailUpdateVerification($mailData));
                        Cache::put(
                            $key . 'change_profile',
                            json_encode([
                                'mobile' => $decoded['mobile'],
                                'email' => $decoded['email'],
                                'changeType' => $decoded['changeType'],
                                'email_otp' => $otp
                            ])
                        );
                    }
                    if ($decoded['changeType'] == 'change_mobile') {
                        if (App::environment('production'))  $user->sendOTP();
                    }
                    if ($decoded['changeType'] == 'change_email') {
                        $otp = App::environment('production')
                            ? rand(100000, 999999)
                            : 123456;

                        $email = $decoded['email'];

                        $mailData = [
                            'title' => 'Email Verification',
                            'body' => 'Please enter the below mentioned OTP for email verification.',
                            'otp' => $otp
                        ];

                        if (App::environment('production')) Mail::to($email)->send(new emailUpdateVerification($mailData));
                        Cache::put(
                            $key . 'change_profile',
                            json_encode([
                                'mobile' => $decoded['mobile'],
                                'email' => $decoded['email'],
                                'changeType' => $decoded['changeType'],
                                'email_otp' => $otp
                            ])
                        );
                    }
                }

                return response()->json([
                    'message' => 'Password verified successfully',
                    'token' => $token,
                    'changeType' => $decoded['changeType']
                ]);
            }
        } else {
            return response()->json([
                'message' => 'Wrong credentials.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function verifyOTp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => ['required_without:email_otp',  'numeric', 'digits_between:4,10'],
            'email_otp' => ['required_without:otp', 'numeric', 'digits_between:4,10'],
            'token' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        /** @var User */
        $user = auth()->user();
        $key = md5($user);
        $data = Cache::get($key . 'token');
        $data = json_decode($data, true);
        if (!$data) {
            return response()->json([
                'message' => 'Token is invalid'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($data['token'] == $request->token) {
            $data = $validator->validated();

            $cached = Cache::get($key . 'change_profile');
            if ($cached) {
                $decoded = json_decode($cached, true);
                $changeType = $decoded['changeType'];

                if ($changeType == 'change_mobile_email') {
                    if ($user->verifyOTP($request->otp)) {
                        if (($data['email_otp'] == $decoded['email_otp'])) {
                            if ($user->update([
                                'mobile' => $decoded['mobile']
                            ])) {
                                if ($user->update([
                                    'email' => $decoded['email']
                                ])) {
                                    return response()->json([
                                        'message' => 'Mobile number and Email address successfully updated'
                                    ]);
                                }
                            }
                        } else{
                            return response()->json([
                                'message' => 'OTP not matched.',
                                'flag' => 'email_otp'
                            ], Response::HTTP_UNPROCESSABLE_ENTITY);
                        }
                    } else {
                        return response()->json([
                            'message' => 'OTP not matched.',
                            'flag' => 'mobile_otp'
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }
                }
                if ($changeType == 'change_mobile') {
                    if ($user->verifyOTP($request->otp)) {
                        if ($user->update([
                            'mobile' => $decoded['mobile']
                        ])) {
                            return response()->json([
                                'message' => 'Mobile number successfully updated'
                            ]);
                        }
                    } else {
                        return response()->json([
                            'message' => 'OTP not matched.',
                            'flag' => 'mobile_otp'
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }
                }
                if ($changeType == 'change_email') {
                    if ($data['email_otp'] == $decoded['email_otp']) {
                        if ($user->update([
                            'email' => $decoded['email']
                        ])) {
                            return response()->json([
                                'message' => 'Email address successfully updated'
                            ]);
                        }
                    } else {
                        return response()->json([
                            'message' => 'OTP not matched.',
                            'flag' => 'email_otp'
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);  // send error
                    }
                }
            }
        } else {
            return response()->json([
                'message' => 'Token is wrong'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function changePassword(Request $request)
    {
        $validator =  Validator::make($request->all(), [
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var User */
        $user = auth()->user();
        if ($user && (hash_sha($request->old_password) === $user->password)) {
            try {
                $user->update(['password' => $request->new_password]);
                return response()->json([
                    'message' => 'Password changed successfully',
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'message' => 'There was an error while updating your password, please try again later.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        return response()->json([
            'message' => 'Your old password is incorrect.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function resendMobileOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        /** @var User */
        $user = auth()->user();
        $key = md5($user);
        $data = Cache::get($key . 'token');
        $data = json_decode($data, true);
        if (!$data) {
            return response()->json([
                'message' => 'Token is invalid'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($data['token'] == $request->token) {
            $data = $validator->validated();

            $cached = Cache::get($key . 'change_profile');
            if ($cached) {
                $decoded = json_decode($cached, true);
                $changeType = $decoded['changeType'];
                if (($changeType == 'change_mobile') || ($decoded['changeType'] == 'change_mobile_email')) {
                    if (App::environment('production'))  $user->sendOTP();
                    return response()->json([
                        'message' => 'Resend otp successfully',
                        'token' => $data['token'],
                        'changeType' => $decoded['changeType']
                    ]);
                }
            }
        }
    }

    public function resendEmailOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        /** @var User */
        $user = auth()->user();
        $key = md5($user);
        $data = Cache::get($key . 'token');
        $data = json_decode($data, true);
        if (!$data) {
            return response()->json([
                'message' => 'Token is invalid'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($data['token'] == $request->token) {
            $data = $validator->validated();

            $cached = Cache::get($key . 'change_profile');
            if ($cached) {
                $decoded = json_decode($cached, true);
                $changeType = $decoded['changeType'];
                if (($changeType == 'change_email') || ($decoded['changeType'] == 'change_mobile_email')) {
                    $otp = App::environment('production')
                        ? rand(100000, 999999)
                        : 123456;

                    $email = $decoded['email'];

                    $mailData = [
                        'title' => 'Email Verification',
                        'body' => 'Please enter the below mentioned OTP for email verification.',
                        'otp' => $otp
                    ];

                    if (App::environment('production')) Mail::to($email)->send(new emailUpdateVerification($mailData));
                    Cache::put(
                        $key . 'change_profile',
                        json_encode([
                            'mobile' => $decoded['mobile'],
                            'email' => $decoded['email'],
                            'changeType' => $decoded['changeType'],
                            'email_otp' => $otp
                        ])
                    );
                    return response()->json([
                        'message' => 'Password verified successfully',
                        'token' => $data['token'],
                        'changeType' => $decoded['changeType']
                    ]);
                }
            }
        }
    }
}
