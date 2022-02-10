<?php

namespace App\Models\Traits;

use App;
use App\Exceptions\ReportableException;
use Exception;
use Illuminate\Http\Response;
use Log;
use Twilio\Rest\Client;

/**
 * Add OTP functionality
 * @author Suraj Jadhav <suraj@skryptech.com>
 */
trait HasOTP
{
    /**
     * Send OTP to provided model
     *
     * @return bool|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function sendOTP()
    {
        // dd($twilio);
        try {
            // $otp = App::environment('production')
            //     ? rand(1000, 9999)
            //     : 123456;

            // $this->update(['otp' => bcrypt($otp)]);

            if (App::environment('production')) {
                // sendOTP($this->mobile, $otp);

                $sid = config('twilio.sid');
                $token = config('twilio.token');

                $twilio = new Client($sid, $token);

                $lookup = $twilio
                    ->lookups
                    ->v1
                    ->phoneNumbers('+1' . $this->mobile)
                    ->fetch(["type" => ["carrier"]]);

                if ($lookup->carrier['type'] != 'mobile') {
                    if (request()->expectsJson()) {
                        return response()->json([
                            'message' => 'This number is not allowed.',
                            'status' => Response::HTTP_UNPROCESSABLE_ENTITY
                        ], Response::HTTP_UNPROCESSABLE_ENTITY)->send();
                    }

                    throw new ReportableException('This number is not allowed.');
                }

                $twilio
                    ->verify
                    ->v2
                    ->services("VAeb5a73bc8bb0e27de7c0e13019d483e7")
                    ->verifications
                    ->create('+1' . $this->mobile, "sms");
            }

            return true;
        } catch (Exception $e) {
            Log::error($e->getMessage());

            if (request()->expectsJson()) {
                return response()->json(['message' => 'There was error sending OTP. Please try again.'], 500);
            }

            throw new ReportableException('There was error sending OTP. Please try again.');
        }
    }

    public function verifyOTP($otp)
    {
        if (! App::environment('production')) return $otp == '123456';

        $sid = config('twilio.sid');
        $token = config('twilio.token');

        $twilio = new Client($sid, $token);

        $verification_check = $twilio
                                ->verify
                                ->v2
                                ->services("VAeb5a73bc8bb0e27de7c0e13019d483e7")
                                ->verificationChecks
                                ->create(
                                    $otp, // code
                                    ["to" => '+1' . $this->mobile]
                                );

        return $verification_check->status == 'approved';
    }
}
