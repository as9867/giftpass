<?php

use App\Domains\Auth\Models\User;
use App\Domains\Card\Models\Brand;
use App\Domains\Card\Models\Card;
use App\Domains\Marketplace\Models\Marketplace;
use App\Domains\Marketplace\Models\Marketplacecards;
use App\Domains\Marketplace\Models\OfferTrades;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\ReportableException;

if (!function_exists('escrow')) {
    /**
     * Helper to grab the application name.
     *
     * @return mixed
     */
    function escrow()
    {
        $id = config('boilerplate.escow_account');
        return User::findOrFail($id);
    }
}

if (!function_exists('hash_sha')) {
    /**
     * Helper to grab the application name.
     *
     * @return mixed
     */
    function hash_sha($value)
    {
        // return Hash::make($value);
        return hash("sha256", $value);
    }
}

if (!function_exists('isValidCard')) {
    function isValidCard($card_id)
    {
        $card = Card::where(['id' => $card_id, 'user_id' => auth()->user()->id, 'active' => 1])->first();
        if (!isset($card)) {
            return 0;
        } else {
            $marketplace_card = Marketplacecards::where('card_id', $card_id)->first();
            if (isset($marketplace_card)) {
                $marketplace = Marketplace::where(['id' => $marketplace_card->marketplace_id, 'user_id' => auth()->user()->id])
                    ->whereIn('status', ['active', 'hold', 'dispute', 'pending_live'])
                    ->first();
                if (isset($marketplace)) {
                    return 2;
                }
            }
            return $card;
        }
    }
}


if (!function_exists('isValidCard')) {
    function paymentIntent($marketPlace_id, $amount, $paymentbywallet, $balance)
    {
        require '../vendor/autoload.php';

        // This is a public sample test API key., $
        // Don’t submit any personally identifiable information in requests made with this key.
        // Sign in to see your own test API key embedded in code samples.
        \Stripe\Stripe::setApiKey('sk_test_tR3PYbcVNZZ796tH88S4VQ2u');
        $marketplace = Marketplace::where('id', $marketPlace_id)->first();
        $amounts = $marketplace->selling_amount;
        $payment_obj = (object)[];

        $selling_amount  = isset($marketplace_id)  ? $amounts : $amount;

        if ($paymentbywallet == false) {
            $finalAmount = $selling_amount;
            $payment_obj->wallet_amount = 0;
            $payment_obj->stripe_amount = $finalAmount;
        } else {
            //$user = User::where('id', $marketplace->buyer_id)->first();
            if ($balance < $selling_amount) {
                $finalAmount = $selling_amount - $balance;
                $payment_obj->wallet_amount = $balance;
                $payment_obj->stripe_amount = $finalAmount;
            } else {
                $payment_obj->wallet_amount = $selling_amount;
                $payment_obj->stripe_amount = 0;
            }
        }








        header('Content-Type: application/json');

        try {
            // retrieve JSON from POST body
            // $jsonStr = file_get_contents('php://input');
            // $jsonObj = json_decode($jsonStr);

            // Create a PaymentIntent with amount and currency
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $finalAmount,
                'currency' => 'eur',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            $output = [
                'clientSecret' => $paymentIntent->client_secret,
            ];

            DB::table('payments')->insert([
                ['user_id' => auth()->user()->id, 'client_secret' => $clientSecret, 'marketplace_id' => $marketPlace_id, 'brand_id' => $brand_id, 'amount' => $finalAmount],

            ]);
            $paument_obj->client_secret = $paymentIntent->client_secret;
            return $payment_obj;
        } catch (Error $e) {
            http_response_code(500);
        }
    }
}
if (!function_exists('notify')) {
    function notify(array $message, string $token)
    {
        // $endpoint = env('FCM_ENDPOINT');
        $key = config('app.fcm_key');
        $fcm_token = fcm_token::where('user_id', auth()->user()->id)->first();

        $fields = [
            'to' => $fcm_token[0]['fcm_token'],
            'priority' => 'high',
            'notification' => [
                'title' => $message['title'],
                'body' => $message['body'],
                'sound' => 'Default'
            ],
            'unread_notifications' => $message['unread_notifications'] ?? 0
        ];

        try {
            // TODO: Update following
            Http::withToken($key)
                ->withHeaders([
                    'Content-Type' => 'application/json'
                ])->post('https://fcm.googleapis.com/fcm/send', $fields);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new ReportableException('Notification could not be sent.');
        }
    }
}

if (!function_exists('activityMessage')) {
    function activityMessage($activity)
    {
        // dd($activity);
        $activity_data = [];

        $bg_color_light_yellow = "#FFEFD3";
        $text_color_yellow = "#FF8000";

        $bg_color_light_sky = "#DCF9F1";
        $text_color_sky = "#2EDCAD";

        $bg_color_light_red = "#F5DBCB";
        $text_color_red = "#EE6055";
        $value = $activity;

        # code... 
        $seconds_ago = (time() - strtotime($value->created_at));

        if ($seconds_ago >= 31536000) {
            $created =  intval($seconds_ago / 31536000) . "y ago";
        } elseif ($seconds_ago >= 2419200) {
            $created =  intval($seconds_ago / 2419200) . "mo ago";
        } elseif ($seconds_ago >= 86400) {
            $created =  intval($seconds_ago / 86400) . "d ago";
        } elseif ($seconds_ago >= 3600) {
            $created =  intval($seconds_ago / 3600) . "h ago";
        } else {
            $created = $value->created_at->format('h:i a');
        }

        $marketplace = Marketplace::where('id', $value->marketplace_id)->first();
        $brand = Brand::where('id', $value->brand_id)->first();
        $listing_type = '';
        $message = '';
        $brand_name = '';
        $selling_amount = null;
        $card = null;
        $brand_logo = '';
        $trading_brand_name = '';
        $trading_brand_logo = '';
        $activity = $value;
        $heading = '';
        $cta = '';
        $text = '';
        $status = '';
        $line_2 = '';
        $status_background_color = $bg_color_light_sky;
        $status_text_color = $text_color_sky;

        if (isset($marketplace)) {
            $listing_type = $marketplace->listing_type;
            $brand_name = $value->brand->name;
            $selling_amount = $marketplace->selling_amount;
            // dd($marketplace->card[0]);
            $brand_logo = $value->brand->logo;
            $card = $marketplace->cards[0]->card->load(['brand']);
            if (($marketplace->listing_type == 'trade')) {
                if (isset($value->offered_brand_id)) {
                    $value = $value->load(['offered_card', 'brand']);
                }
                $trading_brand_name = $marketplace->cards[0]->trading_brand->name;
                $trading_brand_logo = $marketplace->cards[0]->trading_brand->logo;
            }
        }
        if ($value->activity_type == 'list_card') {
            if (($value->action_user_id == $value->reciver_user_id)) {
                $message = "Your " . $brand->name . ' gift card of ' . '$' . $value->amount . " digitized successfully ";
                $heading = 'DGITIZE CARD';
                $status = '';
                $cta = '';
                $text = '';
                $line_2 = '';
                $status_background_color = '';
                $status_text_color = '';
                $marketplace = null;
            }
        } elseif ($value->activity_type == 'add_card_for_sell') {
            if (($value->action_user_id == $value->reciver_user_id)) {
                $message = "Your listing will be live soon. You may edit or delete it within 15 mins";
                $heading = 'DGITIZE CARD';
                $status = $marketplace->status == 'pending_live' ?  'Pending Approval' : '';
                $cta = $marketplace->status == 'pending_live' ? 'button' : 'link';
                $text = $marketplace->status == 'pending_live' ? 'Edit' : 'View Details';
                $line_2 = $marketplace->status == 'pending_live' ? now()->diffInMinutes($marketplace->created_at) . ' min remaining' : "";
                $status_background_color = $bg_color_light_yellow;
                $status_text_color = $text_color_yellow;
            }
        } elseif ($value->activity_type == 'add_card_for_trade') {
            if (($value->action_user_id == $value->reciver_user_id)) {
                $marketplace_cards = $marketplace->cards->map(function ($marketplace_card) {
                    $trading_brand = $marketplace_card->trading_brand;
                    return array_merge($marketplace_card->toArray(), $trading_brand->toArray());
                });
                $message = "Your listing will be live soon. You may edit or delete it within 15 mins";;
                $heading = 'CARD LISTING';
                $cta =  $marketplace->status == 'pending_live' ? 'button' : 'link'; // anuradha-todo
                $text =  $marketplace->status == 'pending_live' ? 'Edit' : 'View Details'; // anuradha-todo
                $status = $marketplace->status == 'pending_live' ? 'Pending Approval' : '';
                $line_2 = $marketplace->status == 'pending_live' ? now()->diffInMinutes($marketplace->created_at) . ' min remaining' : "";
                $status_background_color = $bg_color_light_yellow;
                $status_text_color = $text_color_yellow;
            }
        } elseif ($value->activity_type == 'card_active') {
            if (($value->action_user_id == auth()->user()->id) && ($value->reciver_user_id == auth()->user()->id)) {
                $message = "Your marketplace listing of a " . '$' . $value->amount . ' ' . $brand->name . " card is now live!";;
                $heading = 'MARKETPLACE ACTIVATION';
                $cta = $marketplace->status == 'active' ? 'link' : '';
                $text = $marketplace->status == 'active' ? 'View details' : '';
                $status = '';
                $line_2 = '';
                $status_background_color = '';
                $status_text_color = '';
            }
        }
        // cash 
        elseif ($value->activity_type == 'add_cash') {
            if (($value->action_user_id == auth()->user()->id) && ($value->reciver_user_id == auth()->user()->id)) {
                $message = "Cash Added Successfully";;
                $activity = $value;
                $heading = 'CASH ADDED';
                $status = '';
                $cta = '';
                $text = '';
                $line_2 = '';
                $status_background_color = '';
                $status_text_color = '';
            }
        } elseif ($value->activity_type == 'withdraw_cash') {
            if (($value->action_user_id == auth()->user()->id) && ($value->reciver_user_id == auth()->user()->id)) {
                $message = "Cash withdraw Successfully";;
                $heading = 'CASH WITHDRAW';
                $status = '';
                $cta = '';
                $text = '';
                $line_2 = '';
                $status_background_color = '';
                $status_text_color = '';
            }
        }
        // Purchase
        elseif ($value->activity_type == 'purchase') {
            $max_days = config('app.payment');
            $seconds_ago = (time() - strtotime($value->created_at));
            $day_remaining =  $max_days - intval($seconds_ago / 86400);
            if (($value->reciver_user_id == $value->marketplace_owner_id)) {
                $buyer = User::where('id', $marketplace->buyer_id)->first();
                $message = '@' . $buyer->username . ' initiated a purchase on a gift card you listed. Please wait till the gift card is confirmed to access your money.';;
                $heading = 'CARD SALE';
                $cta = 'link';
                $text = 'View details';
                $status = $marketplace->status == 'hold' ? 'Pending Confirmation' : "";
                $line_2 =   $marketplace->status == 'hold' ? $day_remaining . ' days remaining' : '';
                $status_background_color = $bg_color_light_yellow;
                $status_text_color = $text_color_yellow;
            } else {
                $message = "You initiated a purchase of a " . '$' . $value->amount . ' ' . $brand->name .  ' Gift card.';
                $heading = 'CARD PURCHASE';
                $cta =  $marketplace->status == 'hold' ? 'button' : 'link';
                $text =  $marketplace->status == 'hold' ? 'Confirm Purchase' : 'View Details'; // if marketplace listing is hold other wise other button 
                $status = $marketplace->status == 'hold' ? 'Pending Confirmation' : "";
                $line_2 =   $marketplace->status == 'hold' ? $day_remaining . ' days remaining' : '';
                $status_background_color = $bg_color_light_yellow;
                $status_text_color = $text_color_yellow;
            }
        } elseif ($value->activity_type == 'purchase_completed') {
            if (($value->reciver_user_id == $value->marketplace_owner_id)) {
                $buyer = User::where('id', $marketplace->buyer_id)->first();
                $message = '@' . $buyer->username . ' successfully purchased a ' . $value->amount . 'gift card you listed. The money should appear in your wallet.';
                $heading = 'CARD SELL';
                $cta = $marketplace->status == 'completed' ? '' : '';
                $text = $marketplace->status == 'completed' ? '' : '';
                $status = $marketplace->status == 'completed' ? 'purchase_completed' : ''; // Completed
                $line_2 = '';
                $status_background_color = '#E5E5E5;';
                $status_text_color = '#1794F8;';
            } else {
                $message = "You successfully purchased a " . '$' . $value->amount . ' ' . $brand->name .  ' Gift card.';
                $heading = 'CARD PURCHASE';
                $cta = 'link';
                $text = 'View Details';
                $status = $marketplace->status; // Completed
                $line_2 = '';
                $status_background_color = '#E5E5E5;';
                $status_text_color = '#1794F8;';
            }
        } elseif ($value->activity_type == 'dispute') {
            if (($value->action_user_id == auth()->user()->id) && ($value->reciver_user_id == $value->marketplace_owner_id)) {
                $seller = User::where('id', $value->marketplace_owner_id)->first();
                $message = 'You initiated a complaint on your trade of a ' . '$' . $value->amount . ' ' . $brand->name .  ' Gift card from @' . $seller->username;
                $heading = 'CARD EXCHANGE';
                $cta = 'link';
                $text = 'View Details';
                $status =  $marketplace->status == 'dispute' ? 'Complaint under review' : ''; // if admin confirmed
                $line_2 = '';
                $status_background_color = $bg_color_light_yellow;
                $status_text_color = $text_color_yellow;
            } elseif (($value->action_user_id == auth()->user()->id) && ($value->reciver_user_id != $value->marketplace_owner_id)) {
                if ($marketplace->listing_type == 'trade') {
                    $seller = User::where('id', $value->marketplace_owner_id)->first();
                    $message = 'You initiated a complaint on a trade listing of a ' . '$' . $value->amount . ' ' . $brand->name .  ' Gift card from @' . $seller->username;
                    $heading = 'CARD EXCHANGE';
                    $cta = 'link';
                    $text = 'View Details';
                    $status =  $marketplace->status == 'dispute' ? 'Complaint under review' : '';
                    $line_2 = '';
                    $status_background_color = $bg_color_light_yellow;
                    $status_text_color = $text_color_yellow;
                } else {
                    // dd($value);
                    $seller = User::where('id', $value->marketplace_owner_id)->first();
                    $message = 'You initiated a complaint on purchase of a ' . '$' . $value->amount . ' ' . $brand->name .  ' Gift card from @' . $seller->username;
                    $heading = 'CARD PURCHASE';
                    $cta = 'link';
                    $text = 'View Details';
                    $status =  $marketplace->status == 'dispute' ? 'Complaint under review' : '';
                    $line_2 = '';
                    $status_background_color = $bg_color_light_yellow;
                    $status_text_color = $text_color_yellow;
                }
            } else {
                // $message = '@' . $buyer->username . " initiated a complaint on your " . '$' . $value->amount . ' ' . $brand->name .  ' Gift card.';
                if (($value->action_user_id != auth()->user()->id) && ($value->reciver_user_id == $value->marketplace_owner_id)) {
                    $seller = User::where('id', $value->marketplace_owner_id)->first();
                    if ($marketplace->listing_type == 'trade') {
                        $buyer = User::where('id', $marketplace->buyer_id)->first();
                        $message = '@' . $buyer->username . ' initiated a complaint on your trade of a ' . '$' . $value->amount . ' ' . $brand->name .  ' Gift card from @' . $seller->username;
                        $heading = 'CARD EXCHANGE';
                        $cta = 'link';
                        $text = 'View Details';
                        $status =  $marketplace->status == 'dispute' ? 'Complaint under review' : '';
                        $line_2 = '';
                        $status_background_color = $bg_color_light_yellow;
                        $status_text_color = $text_color_yellow;
                    } else {
                        $buyer = User::where('id', $marketplace->buyer_id)->first();
                        $message = '@' . $buyer->username . ' initiated a complaint on purchase of your ' . '$' . $value->amount . ' ' . $brand->name .  ' Gift card.';
                        $heading = 'CARD PURCHASE';
                        $cta = 'link';
                        $text = 'View Details';
                        $status =  $marketplace->status == 'dispute' ? 'Complaint under review' : '';
                        $line_2 = '';
                        $status_background_color = $bg_color_light_yellow;
                        $status_text_color = $text_color_yellow;
                    }
                } elseif (($value->action_user_id != auth()->user()->id) && ($value->reciver_user_id != $value->marketplace_owner_id)) {
                    $seller = User::where('id', $value->marketplace_owner_id)->first();
                    $message = '@' . $seller->username . 'initiated a complaint on a trade listing of a ' . '$' . $value->amount . ' ' . $brand->name .  ' Gift card from @' . $seller->username;
                    $heading = 'CARD EXCHANGE';
                    $cta = 'link';
                    $text = 'View Details';
                    $status = $marketplace->status == 'dispute' ? 'Complaint under review' : '';
                    $line_2 = '';
                    $status_background_color = $bg_color_light_yellow;
                    $status_text_color = $text_color_yellow;
                }
            }
        } elseif ($value->activity_type == 'dispute_completed') {
            if ($value->status == 'rejected') {
                $status = 'Complaint rejected';
            }
            if ($value->status == 'accepted') {
                $status = 'Complaint approved';
            }

            if (($value->reciver_user_id == $marketplace->buyer_id) and $value->status == 'accepted') {
                $seller = User::where('id', $value->marketplace_owner_id)->first();
                if ($marketplace->listing_type == 'sell') {
                    $heading = 'CARD PURCHASE';
                    $message = 'GiftPass Admin has accepeted your complaint on purchase of a ' . '$' . $value->amount . ' ' . $brand->name .  ' Gift card from @' . $seller->username;
                } else {
                    $heading = 'CARD EXCHANGE';
                    $message = 'GiftPass Admin has accepeted your complaint on trade of a ' . '$' . $value->amount . ' ' . $brand->name .  ' Gift card from @' . $seller->username;
                }

                $cta = 'link';
                $text = 'View Details';
                $status = 'Complaint Approved';
                $line_2 = '';
                $status_background_color = $bg_color_light_sky;
                $status_text_color = $text_color_sky;
            } else if (($value->reciver_user_id == $marketplace->buyer_id) and $value->status == 'rejected') {
                $seller = User::where('id', $value->marketplace_owner_id)->first();
                if ($marketplace->listing_type == 'sell') {
                    $heading = 'CARD PURCHASE';
                    $message = 'GiftPass Admin has rejected your complaint on purchase of a ' . '$' . $value->amount . ' ' . $brand->name .  ' Gift card from @' . $seller->username;
                } else {
                    $heading = 'CARD EXCHANGE';
                    $message = 'GiftPass Admin has rejected your complaint on Trade of a ' . '$' . $value->amount . ' ' . $brand->name .  ' Gift card from @' . $seller->username;
                }
                $cta = 'link';
                $text = 'View Details';
                $status = "Complaint Rejected";
                $line_2 = '';
                $status_background_color = $bg_color_light_red;
                $status_text_color = $text_color_red;
            } else if (($value->reciver_user_id == $marketplace->user_id) and $value->status == 'accepted') {
                $seller = User::where('id', $value->marketplace_owner_id)->first();
                if ($marketplace->listing_type == 'sell') {
                    $status = "Sale Rejected";
                    $heading = 'CARD PURCHASE';
                    $message = 'GiftPass Admin has rejected your sale of a ' . '$' . $value->amount . ' ' . $brand->name .  ' Gift card from @' . $seller->username;
                } else {
                    $status = "Trade Rejected";
                    $heading = 'CARD EXCHANGE';
                    $buyer =  User::where('id', $value->buyer_id)->first();
                    $message = 'GiftPass Admin has rejected your trade of a ' . '$' . $value->amount . ' ' . $brand->name .  ' Gift card.';
                }
                // $heading = 'CARD PURCHASE';
                $cta = 'link';
                $text = 'View Details';
                $line_2 = '';
                $status_background_color = $bg_color_light_red;
                $status_text_color = $text_color_red;
            } else {
                $buyer = User::where('id', $marketplace->buyer_id)->first();
                if ($marketplace->listing_type == 'sell') {
                    $status = "Sale Approved";
                    $heading = 'CARD PURCHASE';
                    $message = "GiftPass Admin has approved the sale on your " . '$' . $value->amount . ' ' . $brand->name .  ' Gift card.';
                } else {
                    $status = "Trade Approved";
                    $heading = 'CARD EXCHANGE';
                    $message = "GiftPass Admin has approved the trade on your " . '$' . $value->amount . ' ' . $brand->name .  ' Gift card.';
                }
                $cta = 'link';
                $text = 'View Details';
                // $status = "Sale Approved";
                $line_2 = '';
                $status_background_color = $bg_color_light_sky;
                $status_text_color = $text_color_sky;
            }
        }

        // trade offers
        elseif ($value->activity_type == 'place_trade_offer') {
            $marketplace = Marketplace::where('id', $value->marketplace_id)->first();
            $load = $marketplace->load(['cards.card']);
            $marketplace->cards->map(function ($marketplace_card) {
                $trading_brand = $marketplace_card->trading_brand;
            });
            $marketplace_offers = $marketplace->offer_trades;
            $total_offers = count($marketplace_offers);
            if (($value->reciver_user_id == $value->marketplace_owner_id)) {
                $buyer = User::where('id', $value->action_user_id)->first();
                $message = "You received a request to swap your " . $value->brand->name . " Gift card for a " . $value->offered_card->name . " Gift card from " . '@' . $buyer->username;
                $heading = 'CARD EXCHANGE';
                $cta = $marketplace->status == 'active' ? 'button' : '';
                $text = $marketplace->status == 'active' ? 'View Offers(' . $total_offers . ')' : 'View Details';
                $status = ''; //$marketplace->status == 'active' ? 'Active' : '';
                $line_2 = '';
                $status_background_color = $bg_color_light_sky;
                $status_text_color = $text_color_sky;
            } else {
                $seller =  User::where('id', $marketplace->user_id)->first();
                $message = "You placed a request to swap your " . $value->offered_card->name  . " Gift card for a " . $value->brand->name . " Gift card from " . '@' . $seller->username;
                $heading = 'CARD EXCHANGE';
                $cta = 'link';
                $text = $marketplace->status == 'active' ? 'View Offer' : 'View Details';
                $status = '';
                $line_2 = '';
                $status_background_color = $bg_color_light_sky;
                $status_text_color = $text_color_sky;
            }
        } elseif ($value->activity_type == 'trade_offer_accepted') {
            $buyer = User::where('id', $marketplace->buyer_id)->first();
            $marketplace = Marketplace::where('id', $value->marketplace_id)->first();
            $load = $marketplace->load(['cards.card']);
            $marketplace->cards->map(function ($marketplace_card) {
                $trading_brand = $marketplace_card->trading_brand;
            });
            $trade_offer = OfferTrades::where('marketplace_id', $value->marketplace_id)->where('status', '!=', 'rejected')->where('status', '!=', 'pending')->first();

            if (($value->action_user_id == $value->marketplace_owner_id) && ($value->reciver_user_id == $value->marketplace_owner_id)) {
                if (($trade_offer->status == 'accepted') || ($trade_offer->status == 'accepted_by_buyer')) {
                    $text = $marketplace->status == 'hold' ? 'Confirm Received Card' : 'View Details';
                    $cta = $marketplace->status == 'hold' ? 'button' : 'link';
                    $status = '';
                } else if ($trade_offer->status == 'accepted_by_seller') {
                    $text = 'View Details';
                    $cta = 'link';
                    $status = 'Pending Buyer Approval';
                } else {
                    $text = 'View Details';
                    $cta = 'link';
                    $status = '';
                }
                $message = "You accepted a trade offer request to swap your " . $value->brand->name . " Gift card for a " . $value->offered_card->name . " Gift card from " . '@' . $buyer->username . '.';
            }
            if (($value->action_user_id == $value->marketplace_owner_id) && ($value->reciver_user_id != $value->marketplace_owner_id)) {

                if (($trade_offer->status == 'accepted') || ($trade_offer->status == 'accepted_by_seller')) {
                    $text = $marketplace->status == 'hold' ? 'Confirm Received Card' : 'View Details';
                    $cta = $marketplace->status == 'hold' ? 'button' : 'link';
                    $status = '';
                } else if ($trade_offer->status == 'accepted_by_buyer') {
                    $text = 'View Details';
                    $cta = 'link';
                    $status = 'Pending Seller Approval';
                } else {

                    $text = 'View Details';
                    $cta = 'link';
                    $status = '';
                }
                $message = "Your trade offer request to swap " . $value->brand->name . " Gift card for a " . $value->offered_card->name . " Gift card was accepted." . '';
            }
            $heading = 'CARD EXCHANGE';
            $cta = $cta;
            $text = $text;
            $status = $status;
            $line_2 = '';
            $status_background_color = $bg_color_light_yellow;
            $status_text_color = $text_color_yellow;
        } elseif ($value->activity_type == 'trade_offer_rejected') {
            if (($value->action_user_id == $value->marketplace_owner_id)) {
                $buyer = User::where('id', $value->action_user_id)->first();
                $marketplace = Marketplace::where('id', $value->marketplace_id)->first();
                $load = $marketplace->load(['cards.card']);
                $marketplace->cards->map(function ($marketplace_card) {
                    $trading_brand = $marketplace_card->trading_brand;
                });
                $message = "You rejected a trade offer request to swap your " . $value->brand->name . " Gift card for a " . $value->offered_card->name . " Gift card from " . '@' . $buyer->username;
                $heading = 'CARD EXCHANGE';
                $cta = 'link';
                $text = 'View Details';
                $status = $marketplace->status;
                $line_2 = '';
                $status_background_color = 'pink';
                $status_text_color = 'red';
            }
            if (($value->action_user_id == $value->marketplace_owner_id) && ($value->reciver_user_id == auth()->user()->id)) {
                $buyer = User::where('id', $value->action_user_id)->first();
                $marketplace = Marketplace::where('id', $value->marketplace_id)->first();
                $load = $marketplace->load(['cards.card']);
                $marketplace->cards->map(function ($marketplace_card) {
                    $trading_brand = $marketplace_card->trading_brand;
                });
                $message = "Your trade offer request rejected to swap " . $value->brand->name . " Gift card for a " . $value->offered_card->name . " Gift card ";
                $heading = 'CARD EXCHANGE';
                $cta = 'link';
                $text = 'View Details';
                $status = $marketplace->status;
                $line_2 = '';
                $status_background_color = '';
                $status_text_color = '';
            }
        } elseif ($value->activity_type == 'accepted_by_buyer') {
            $buyer = User::where('id', $value->action_user_id)->first();
            $marketplace = Marketplace::where('id', $value->marketplace_id)->first();
            $load = $marketplace->load(['cards.card']);
            $marketplace->cards->map(function ($marketplace_card) {
                $trading_brand = $marketplace_card->trading_brand;
            });
            if (($value->action_user_id != $value->marketplace_owner_id) && ($value->reciver_user_id == $value->marketplace_owner_id)) {
                $text = $marketplace->status == 'hold' ? 'Confirm Received Card' : 'View Details';
                $cta = $marketplace->status == 'hold' ? 'button' : 'link';
                $status = 'Approval Pending';
                $message = '@' . $buyer->username . " accepted your traded card";
            } else {
                $cta = "link";
                $text = "View Details";
                $status = "Awaiting Seller Approval";
                $message = "You accepted the traded card from " . '@' . $buyer->username;
            }
            $heading = 'CARD EXCHANGE';
            $cta = $cta;
            $text = $text;
            $status = $status; //Awaiting Seller Approval
            $line_2 = '';
            $status_background_color = $bg_color_light_yellow;
            $status_text_color = $text_color_yellow;
        } elseif ($value->activity_type == 'accepted_by_seller') {
            $buyer = User::where('id', $marketplace->buyer_id)->first();
            $marketplace = Marketplace::where('id', $value->marketplace_id)->first();
            $load = $marketplace->load(['cards.card']);
            $marketplace->cards->map(function ($marketplace_card) {
                $trading_brand = $marketplace_card->trading_brand;
            });
            if (($value->action_user_id == $value->marketplace_owner_id) && ($value->reciver_user_id == $value->marketplace_owner_id)) {
                $cta = "link";
                $text = "View Details";
                $status = "Awaiting Buyer Approval";
                $message = "You accepted the traded card from " . '@' . $buyer->username . ".";
            } else {
                $text = $marketplace->status == 'hold' ? 'Confirm Received Card' : '';
                $cta = 'button';
                $status = 'Approval Pending';
                $message = "Your traded card was accepted by the seller. Please confirm the card received by you.";
            }
            $heading = 'CARD EXCHANGE';
            $cta = $cta;
            $text = $text;
            $status = $status;
            $line_2 = '';
            $status_background_color = $bg_color_light_yellow;
            $status_text_color = $text_color_yellow;
        } elseif ($value->activity_type == 'accepted_by_both') {

            $buyer = User::where('id', $value->action_user_id)->first();
            $marketplace = Marketplace::where('id', $value->marketplace_id)->first();
            $load = $marketplace->load(['cards.card']);
            $marketplace->cards->map(function ($marketplace_card) {
                $trading_brand = $marketplace_card->trading_brand;
            });
            $message = "Traded cards were accepted by both users";
            $heading = 'CARD EXCHANGE';
            $cta = 'link';
            $text = 'View Details';
            $status = 'Trade Completed';
            $line_2 = '';
            $status_background_color = $bg_color_light_sky;
            $status_text_color = $text_color_sky;
        } elseif ($value->activity_type == 'trade_offer_withdraw') {
            $buyer = User::where('id', $value->action_user_id)->first();
            $marketplace = Marketplace::where('id', $value->marketplace_owner_id)->first();
            $load = $marketplace->load(['cards.card']);
            $marketplace->cards->map(function ($marketplace_card) {
                $trading_brand = $marketplace_card->trading_brand;
            });
            if (($value->reciver_user_id == $value->marketplace_id)) {
                $message = "@" . $buyer->username . "withdraw offered card swap request of" . $value->brand->name . " Gift card for a " . $value->offered_card->name . " Gift card ";
            } else {
                $message = "you withdraw offered card swap request of" . $value->brand->name . " Gift card for a " . $value->offered_card->name . " Gift card ";
            }
            $heading = 'CARD EXCHANGE';
            $cta = 'link';
            $text = '';
            $status = $marketplace->status;
            $line_2 = '';
            $status_background_color = '';
            $status_text_color = '';
        }
        if (isset($value->brand)) {
            unset($value->brand);
        }

        $activity_data = [
            'created' => $created,
            'listing_type' => $listing_type,
            'message' => $message,
            'brand_name' => $brand_name,
            'brand_logo' => $brand_logo,
            'selling_amount' => $selling_amount,
            'trading_brand_name' => $trading_brand_name,
            'trading_brand_logo' => $trading_brand_logo,
            'heading' => $heading,
            'cta' => $cta,
            'text' => $text,
            'status' => $status,
            'line 2' => $line_2,
            'status_background_color' => $status_background_color,
            'status_text_color' => $status_text_color,
            'card' => $card,
            // 'marketplace' => $marketplace,
            'activity' => $value,
        ];
        return $activity_data;
    }
}
