<?php

namespace App\Domains\Marketplace\Http\Controllers\API;

use App\Domains\Activity\Models\Activity;

use App\Domains\Auth\Models\User;
use App\Domains\Auth\Models\WalletTransaction;
use App\Domains\Card\Models\Card;
use App\Domains\Marketplace\Models\Bidding;
use App\Domains\Marketplace\Models\Marketplace;
use App\Domains\Marketplace\Models\Marketplacecards;
use App\Domains\Marketplace\Models\Bank_accounts;
use Log;
use App\Http\Controllers\Controller;
use Http;
use Illuminate\Http\Request;
use App\Mail\welcomeMail;
use Bavix\Wallet\Exceptions\AmountInvalid;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;

class PaymentController extends Controller
{
    public function acceptPayment(Request $request)
    {
        $validatedData = $request->validate([
            'marketplace_id' => 'required|int',
            'status' => ["required", "max:255", "regex:(accepted|rejected)"],
            'dispute_message' => 'required_if:status,rejected',
        ]);

        $marketplace = Marketplace::where('id', $validatedData['marketplace_id'])->where('buyer_id', auth()->user()->id)->where('status', 'hold')->first();
        if (isset($marketplace)) {
            $marketplace_card = Marketplacecards::where('marketplace_id', $marketplace->id)->first();
            $card_data = Card::where('id', $marketplace_card->card_id)->first();
            if ($validatedData['status'] == 'accepted') {
                if ($marketplace->listing_type == 'trade') {
                    $marketplace->status = 'completed';
                    $marketplace->save();

                    // activity save

                    $activity = [];
                    $activity['action_user_id'] = auth()->user()->id;
                    $activity['reciver_user_id'] = $marketplace->user_id;
                    $activity['activity_type'] = 'purchase_completed';
                    $activity['marketplace_id'] = $marketplace->id;
                    $activity['brand_id'] = $card_data->brand_id;
                    $activity['amount'] = $marketplace->selling_amount;
                    $activity['status'] = 'accepted';
                    $activity = Activity::create($activity);

                    $activity = [];
                    $activity['action_user_id'] = auth()->user()->id;
                    $activity['reciver_user_id'] = auth()->user()->id;
                    $activity['activity_type'] = 'purchase_completed';
                    $activity['marketplace_id'] = $marketplace->id;
                    $activity['brand_id'] = $card_data->brand_id;
                    $activity['amount'] = $marketplace->selling_amount;
                    $activity['status'] = 'accepted';
                    $activity = Activity::create($activity);
                } else {
                    // $admin_user = escrow();
                    $admin_user = User::where('id', config('app.ADMIN_ID'))->first();
                    $marketplace_user = User::where('id', $marketplace->user_id)->first();
                    if ($marketplace->listing_type == 'auction') {
                        $bid = Bidding::where('marketplace_id', $marketplace->id)->where('user_id', auth()->user()->id)->first();
                        if (!isset($bid)) {
                            return response()->json(['status' => 400,  'message' => __('stringMessage.something_wrong')]);
                        }
                        $amount =  $bid->bidding_amount;
                    } else {
                        $amount = $marketplace->selling_amount;
                    }
                    $transfer = $admin_user->transfer($marketplace_user, $amount);
                    // $transfer = $admin_user->transfer($marketplace_user, 0);
                    if (isset($transfer)) {
                        $activity = [];
                        $activity['action_user_id'] = auth()->user()->id;
                        $activity['reciver_user_id'] = $marketplace->user_id;
                        $activity['activity_type'] = 'purchase_completed';
                        $activity['marketplace_id'] = $marketplace->id;
                        $activity['brand_id'] = $card_data->brand_id;
                        $activity['amount'] = $amount;
                        $activity['marketplace_owner_id'] = $marketplace->user_id;
                        $activity['status'] = 'accepted';
                        $activity = Activity::create($activity);

                        $activity = [];
                        $activity['action_user_id'] = auth()->user()->id;
                        $activity['reciver_user_id'] = auth()->user()->id;
                        $activity['activity_type'] = 'purchase_completed';
                        $activity['marketplace_id'] = $marketplace->id;
                        $activity['brand_id'] = $card_data->brand_id;
                        $activity['amount'] = $amount;
                        $activity['marketplace_owner_id'] = $marketplace->user_id;
                        $activity['status'] = 'accepted';
                        $activity = Activity::create($activity);

                        $walletData = [];
                        $walletData['activity_id'] = $activity->id;
                        $walletData['marketplace_id'] = $marketplace->id;
                        $walletData['transaction_type'] = 'transfer';
                        $walletData['amount'] = $amount;
                        $activity['marketplace_owner_id'] = $marketplace->user_id;
                        $walletData['from_user'] = $admin_user->id;
                        $walletData['to_user'] = $marketplace->user_id;
                        $walletData['status'] = 'completed';
                        WalletTransaction::create($walletData);

                        $marketplace->status = 'completed';
                        $marketplace->save();

                        if ($marketplace->listing_type == 'auction') {
                            $bid = Bidding::where('marketplace_id', $marketplace->id)->where('user_id', auth()->user()->id)->update(['payment_status' => 'peyment-completed']);
                        }
                    }
                }
                if (App::environment('production')) {
                    // buyer mail
                    $email = 'anuradha@skryptech.com';
                    $mailData = [
                        'subject' => 'Buying a marketplace listed gift card',
                        'title' => 'Buying a marketplace listed gift card',
                        'body' => 'Hello [User Name], you have purchased a [$Amount] [Brand Name] gift card! You can now use it to make purchases in-store or online. You can also chose to Gift it. Decisions, decesions!',
                    ];
                    Mail::to($email)->send(new welcomeMail($mailData));

                    // seller mail
                    $email = 'anuradha@skryptech.com';
                    $mailData = [
                        'subject' => 'Sold a marketplace listed gift card',
                        'title' => 'Sold a marketplace listed gift card',
                        'body' => "Hello [User Name], you have sold your Amount [Brand] gift card for [Amount Sold]! If you've signed up for a GiftPass Debit Card the balance will be made available instantly to use for purchases made via your Apple Pay Wallet. For cash withdrawls it will take between 7-10 working days to clear in your account.",
                    ];
                    Mail::to($email)->send(new welcomeMail($mailData));
                }
                return response()->json(['status' => 200, 'message' => __('stringMessage.status_changed')]);
            }
            if ($validatedData['status'] == 'rejected') {

                $marketplace->status = 'dispute';
                $marketplace->dispute_message = $request->dispute_message;
                $marketplace->save();

                $activity = [];
                $activity['action_user_id'] = auth()->user()->id;
                $activity['reciver_user_id'] = $marketplace->user_id;
                $activity['activity_type'] = 'dispute';
                $activity['marketplace_id'] = $marketplace->id;
                $activity['brand_id'] = $card_data->brand_id;
                $activity['amount'] = $marketplace->selling_amount;
                $activity['marketplace_owner_id'] = $marketplace->user_id;
                $activity['status'] = 'dispute';
                $activity = Activity::create($activity);

                $activity_second = [];
                $activity_second['action_user_id'] = auth()->user()->id;
                $activity_second['reciver_user_id'] = auth()->user()->id;
                $activity_second['activity_type'] = 'dispute';
                $activity_second['marketplace_id'] = $marketplace->id;
                $activity_second['brand_id'] = $card_data->brand_id;
                $activity_second['amount'] = $marketplace->selling_amount;
                $activity_second['marketplace_owner_id'] = $marketplace->user_id;
                $activity_second['status'] = 'dispute';
                $activity_second = Activity::create($activity_second);

                return response()->json(['status' => 200, 'message' => __('stringMessage.dispute_requested')]);
            }
        } else {
            return response()->json(['status' => 200, 'message' => __('stringMessage.something_wrong')]);
        }
    }

    public function token_plaid()
    {

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post('https://sandbox.plaid.com/link/token/create', [
                'client_id' => config('app.plaid_client_id'),
                'secret' => config('app.plaid_secret'),
                'client_name' => 'anuradha',
                "user" => ["client_user_id" => "custom_anuradha"],
                'products' => ["auth"],
                'country_codes' => ['US'],
                'language' => 'en',
            ]);
        if ($response->ok()) {
            return response()->json(['status' => 200, 'data' => $response->json(), 'message' => __('stringMessage.link_token')]);
        }
    }

    public function add_bank_account(Request $request)
    {


        $response = Http::withHeaders(['Content-Type' => 'application'])
            ->post('https://sandbox.plaid.com/item/public_token/exchange', [
                'client_id' => config('app.plaid_client_id'),
                'secret' => config('app.plaid_secret'),
                // 'public_token' => $request->metadataJSON->accounts->public_token,
                'public_token' => 'public-sandbox-cedfecd8-35c2-4c9a-aec0-268c6f1e210b',
            ]);

        dd($response);
        if ($response->ok()) {

            $bank = [];
            $bank['bank_name'] = $request->metadataJSON->institution->name;
            $bank['account_id'] = $request->metadataJSON->account_id;
            $bank['mask'] = $request->metadataJSON->account->mask;
            $bank['subtype'] = $request->metadataJSON->account->subtype;
            $bank['access_token'] = $response->access_token;
            $bank['user_id'] = auth()->user()->id;

            $bank = Bank_accounts::create($bank);

            return response()->json(['status' => 200, 'data' => $response->json(), 'message' => __('stringMessage.link_token')]);
        }
    }
    public function delete_account(Request $request)
    {
        $bank_id = $request->bank_id;

        $Bank_accounts = Bank_accounts::where('id', $bank_id)->where('user_id', auth()->user()->id)->delete();

        if (!empty($bank_accounts)) {

            return response()->json(['status' => 200, 'data' => [], 'message' => 'bank account deleted successfully.']);
        } else {
            return response()->json(['status' => 400, 'data' => [], 'message' => 'something went wrong']);
        }
    }


    public function get_bank_accounts(Request $request)
    {
        $bank_accounts = Bank_accounts::where('user_id', $auth()->user()->id)->get();

        if (!empty($bank_accounts)) {

            return response()->json(['status' => 200, 'data' => $bank_accounts->json(), 'message' => __('stringMessage.link_token')]);
        } else {
            return response()->json(['status' => 404, 'data' => [], 'message' => __('stringMessage.link_token')]);
        }
    }

    public function transfer(Request $request)
    {

        $bank_id = $request->bank_id;
        $amount = $request->amount;
        // $bank_accounts = Bank_accounts::where('id', $bank_id)->where('user_id', auth()->user()->id)->first();
        // $response = Http::withHeaders(['Content-Type' => 'application'])
        //     ->post('https://sandbox.plaid.com/processor/stripe/bank_account_token/create', [
        //         'client_id' => config('app.plaid_client_id'),
        //         'secret' => config('app.plaid_secret'),
        //         'access_token' => $bank_accounts->access_token,
        //         'account_id' => $bank_accounts->account_id,
        //     ]);
        // if ($response->ok()) {
        $stripe = new \Stripe\StripeClient(
            'sk_test_51KK7DeK0KZjvxE0zs0ma4VBzghTusnc8aZ9Pb4bkqTDQ4zq2LoWGTAFcYptnYOXqiTENrOYQwZeoGzKKeOJpKS2d007f1PGAxc'
        );

        // $token = $stripe->tokens->retrieve(
        //     'btok_1KQnp5K0KZjvxE0zCd502GOX',
        //     []
        // );

        $bank = $stripe->accounts->create(
            [
                'country' => 'US',
                'type' => 'custom',
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ]
            ]

        );




        $ext = $stripe->accounts->createExternalAccount(
            $bank->id,
            [
                'external_account' => 'btok_1KQq4EK0KZjvxE0zATenLJKO',
            ]
        );

        $stripe->accounts->update(
            $ext->account,
            ['tos_acceptance' => ['date' => 1609798905, 'ip' => '8.8.8.8']]
        );

        // $external_account_info = $stripe->->create(array("external_account" => 'btok_1KQnp5K0KZjvxE0zCd502GOX'));
        $resp = $stripe->transfers->create([
            'amount' => $amount,
            'currency' => 'usd',
            'destination' => $ext->account,
            'transfer_group' => 'ORDER_95',
        ]);
        dd($resp);
        $admin_user = User::where('id', config('app.ADMIN_ID'))->first();


        $walletData = [];
        //   $walletData['activity_id'] = $activity->id;
        //   $walletData['marketplace_id'] = $marketplace->id;
        $walletData['transaction_type'] = 'withdraw_cash';
        $walletData['amount'] = $amount;
        //   $activity['marketplace_owner_id'] = $marketplace->user_id;
        $walletData['from_user'] = auth()->user()->id;
        $walletData['to_user'] = $admin_user->id;
        $walletData['status'] = 'pending';
        WalletTransaction::create($walletData);
        return response()->json(['status' => 200, 'data' => $transfer->json(), 'message' => __('stringMessage.link_token')]);
        // } else {
        //     return response()->json(['status' => 400,  'message' => __('stringMessage.something_wrong')]);
        // }
    }

    public function stripeWebhook(Request $request)
    {

        $endpoint_secret = 'whsec_36d2890b91fedbbdd125e8cc5b038365e81b7dd28d3e7c00ea42891325f0cf4c';

        $payload = $request->getContent();
        Log::info($payload);
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return response()->json([
                'message' => 'Invalid payload',
            ], 200);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return response()->json([
                'message' => 'Invalid signature',
            ], 200);
        }

        if ($event->type == "charge.succeeded") {

            $intent = $event->data->object;

            //$this->completeOrderInDatabase()
            //$this->sendMail();

            return response()->json([
                'intentId' => $intent->id,
                'message' => 'Payment succeded'
            ], 200);
        } elseif ($event->type == "charge.failed") {
            //Payment failed 

            $intent = $event->data->object;
            $error_message = $intent->last_payment_error ? $intent->last_payment_error->message : "";

            return response()->json([
                'intentId' => $intent->id,
                'message' => 'Payment failed: ' . $error_message
            ], 400);
        }
    }
}
