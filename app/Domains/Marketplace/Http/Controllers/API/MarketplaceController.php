<?php


namespace App\Domains\Marketplace\Http\Controllers\API;

use App\Domains\Auth\Models\User;
use App\Domains\Auth\Models\WalletTransaction;
use App\Domains\Card\Models\Card;
use App\Domains\Marketplace\Models\Bidding;
use App\Domains\Marketplace\Models\Marketplace;
use App\Domains\Marketplace\Models\Marketplacecards;
use App\Domains\Marketplace\Models\OfferDetails;
use App\Domains\Marketplace\Models\OfferTrades;
use App\Domains\Activity\Models\Activity;
use App\Domains\Card\Models\Brand;
use App\Domains\Marketplace\Models\Gift;
use App\Http\Controllers\Controller;
use DB;
use Auth;
use Carbon\Carbon;
use Doctrine\DBAL\Query;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Mail\welcomeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;

class MarketplaceController extends Controller
{
    public function marketplace(Request $request)
    {
    }

    public function CardAddInSellMarketplace(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'selling_amount' => 'required|int',
            'card_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $is_valid = isValidCard($request->card_id);
        if (!is_object($is_valid)) {
            if ($is_valid == 0) {
                return response()->json(['status' => 400, 'message' => __('stringMessage.card_validation')]);
            }
            if ($is_valid == 2) {
                return response()->json(['status' => Response::HTTP_UNPROCESSABLE_ENTITY, 'message' =>  __('stringMessage.card_exist_in_marketplace')]);
            }
        }

        $data = [];
        $data['user_id'] = auth()->user()->id;
        $data['selling_amount'] = $request->selling_amount;
        $data['listing_type'] = 'sell';
        // $data['status'] = 'pending_live';
        $data['status'] = 'active';
        $marketplace = Marketplace::create($data);

        $activity = [];
        $activity['action_user_id'] = auth()->user()->id;
        $activity['reciver_user_id'] = auth()->user()->id;
        $activity['brand_id'] = $is_valid->brand_id;
        $activity['activity_type'] = 'add_card_for_sell';
        $activity['marketplace_id'] = $marketplace->id;
        $activity['marketplace_owner_id'] = auth()->user()->id;
        $activity['amount'] = $request->selling_amount;
        $activity_obj = Activity::create($activity);



        if (isset($marketplace)) {
            $cardData = [];
            $cardData['marketplace_id'] = $marketplace->id;
            $cardData['card_id'] = $request->card_id;
            $cardData['brand_id'] = $is_valid->brand_id;
            $cardData['type'] = 'list';
            $marketplace_card = Marketplacecards::create($cardData);
            $msg = activityMessage($activity_obj);
            if (App::environment('production')) {
                $email = 'anuradha@skryptech.com';

                $mailData = [
                    'subject' => 'Listing a card on the marketplace',
                    'title' => 'Listing a card on the marketplace',
                    'body' => 'Hello @' . auth()->user()->username . ', you have successfully listed your ' . $request->selling_amount . ' ' . $is_valid->brand->name . ' gift card for sale on the GiftPass Market Place!',
                ];
                Event::dispatch(new SendMail(auth()->user()->id, $mailData));

                // Mail::to($email)->send(new welcomeMail($mailData));
                $message = array('title' => "Card listed for sell", 'body' => "Your Amazon gift card has sold!");
                $this->notify(array($msg['message'], auth()->user()->id));
            }

            return response()->json(['status' => 200, 'data' => $marketplace, 'message' => __('stringMessage.card_listed')]);
        }
    }

    public function cardAddInTradeMarketplace(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'my_card_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $is_valid = isValidCard($request['my_card_id']);
        if (!is_object($is_valid)) {
            if ($is_valid == 0) {
                return response()->json(['status' => 400, 'message' => __('stringMessage.card_validation')]);
            }
            if ($is_valid == 2) {
                return response()->json(['status' =>  Response::HTTP_UNPROCESSABLE_ENTITY, 'message' =>  __('stringMessage.card_exist_in_marketplace')]);
            }
        }
        // return $is_valid;
        $data = [];
        $data['user_id'] = auth()->user()->id;
        if (isset($request['trading_amount'])) {
            $data['selling_amount'] = $request['trading_amount'];
        } else {
            $data['selling_amount'] = null;
            $request['trading_amount'] = null;
        }
        $data['listing_type'] = 'trade';
        $data['message'] = $request['message'];
        $data['status'] = 'pending_live';
        $marketplace = Marketplace::create($data);

        $activity = [];
        $activity['action_user_id'] = auth()->user()->id;
        $activity['reciver_user_id'] = auth()->user()->id;
        $activity['brand_id'] = $is_valid->brand_id;
        $activity['activity_type'] = 'add_card_for_trade';
        $activity['marketplace_owner_id'] = auth()->user()->id;
        $activity['marketplace_id'] = $marketplace->id;
        if (isset($request['trading_brand_id'])) {
            $activity['offered_brand_id'] = $request['trading_brand_id'];
        }
        if (isset($request['trading_amount'])) {
            $activity['amount'] = $request['trading_amount'];
        }
        Activity::create($activity);

        if (isset($marketplace)) {
            $cardData = [];
            $cardData['marketplace_id'] = $marketplace->id;
            $cardData['card_id'] = $request['my_card_id'];
            $cardData['type'] = 'trade';
            if (isset($request['trading_brand_id'])) {
                $cardData['trading_brand_id'] = $request['trading_brand_id'];
            }
            $cardData['brand_id'] = $is_valid->brand_id;
            if (isset($request['trading_amount'])) {
                $cardData['trading_amount'] = $request['trading_amount'];
            }
            if (isset($request['recive_other_brands']) && isset($request['trading_brand_id'])) {
                $cardData['recive_other_brands'] = $request['recive_other_brands'];
            } else {
                $cardData['recive_other_brands'] = 1;
            }
            $marketplace_card = Marketplacecards::create($cardData);

            if (App::environment('production')) {
                $email = 'anuradha@skryptech.com';

                $mailData = [
                    'subject' => 'Listing a card on the marketplace',
                    'title' => 'Listing a card on the marketplace',
                    'body' => 'Hello @' . auth()->user()->username . ', you have successfully listed your ' . $is_valid->value . ' ' . $is_valid->brand->name . ' gift card for trade on the GiftPass Market Place!',
                ];
                Event::dispatch(new SendMail(auth()->user()->id, $mailData));

                // Mail::to($email)->send(new welcomeMail($mailData));
            }
            return response()->json(['status' => 200, 'data' => $marketplace, 'message' => __('stringMessage.card_listed')]);
        }
    }

    // public function cardAddInAuctionMarketplace(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'minbid' => 'required|int',
    //         'card_id' => 'required',
    //         'auction_endtime' => 'required',
    //         'reserved_price' => 'nullable',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
    //     }

    //     $is_valid = isValidCard($request['card_id']);
    //     if (!is_object($is_valid)) {
    //         if ($is_valid == 0) {
    //             return response()->json(['status' => 400, 'message' => __('stringMessage.card_validation')]);
    //         }
    //         if ($is_valid == 2) {
    //             return response()->json(['status' => 400, 'message' =>  __('stringMessage.card_exist_in_marketplace')]);
    //         }
    //     }
    //     if (!isset($request->reserved_price)) {
    //         $request->reserved_price = null;
    //     }
    //     $data = [];
    //     $data['user_id'] = auth()->user()->id;
    //     $data['selling_amount'] = $request->reserved_price;
    //     $data['listing_type'] = 'auction';
    //     $data['bidding_expiry'] = $request->auction_endtime;
    //     $data['status'] = 'pending_live';
    //     $data['minbid'] = $request->minbid;
    //     $marketplace = Marketplace::create($data);

    //     $activity = [];
    //     $activity['action_user_id'] = auth()->user()->id;
    //     $activity['reciver_user_id'] = auth()->user()->id;
    //     $activity['activity_type'] = 'add_card_for_auction';
    //     $activity['marketplace_id'] = $marketplace->id;
    //     $activity['brand_id'] = $is_valid->brand_id;
    //     $activity['marketplace_owner_id'] = auth()->user()->id;
    //     $activity['amount'] = $request->reserved_price;
    //     Activity::create($activity);

    //     if (isset($marketplace)) {
    //         $cardData = [];
    //         $cardData['marketplace_id'] = $marketplace->id;
    //         $cardData['card_id'] = $request->card_id;
    //         $cardData['brand_id'] = $is_valid->brand_id;
    //         $cardData['type'] = 'list';
    //         $marketplace_card = Marketplacecards::create($cardData);

    //         return response()->json(['status' => 200, 'data' => $marketplace, 'message' => __('stringMessage.card_listed')]);
    //     }
    // }

    // update marketplace cards

    public function CardUpdateInSellMarketplace(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'selling_amount' => 'required|int',
            'card_id' => 'required',
            'marketplace_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $marketplace = Marketplace::where(['id' => $request->marketplace_id, 'listing_type' => 'sell', 'status' => 'pending_live', 'user_id' => auth()->user()->id])->first();
        $is_valid = isValidCard($request->card_id);
        if (!is_object($is_valid)) {
            if ($is_valid == 0) {
                return response()->json(['status' => 400, 'message' => __('stringMessage.card_validation')]);
            }
        }
        $card = Card::where('id', $request->card_id)->where('user_id', auth()->user()->id)->first();
        if (isset($marketplace)) {
            $marketplace_card = Marketplacecards::where(['marketplace_id' => $marketplace->id, 'active' => 1])->first();
            if (isset($marketplace_card)) {
                $update = Marketplacecards::where(['marketplace_id' => $marketplace->id, 'active' => 1])->update(['card_id' => $request->card_id, 'brand_id' => $card->brand_id]);
                $updateMarketplace = Marketplace::where(['id' => $request->marketplace_id, 'user_id' => auth()->user()->id])->update(['selling_amount' => $request->selling_amount]);
                if (isset($update) && isset($updateMarketplace)) {
                    return response()->json(['status' => 200, 'message' => __('stringMessage.marketplace_card_edited')]);
                }
            }
        }
        return response()->json(['status' => 400, 'message' => __('stringMessage.something_wrong')]);
    }

    public function cardUpdateInTradeMarketplace(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'my_card_id' => 'required',
            'trading_amount' => 'nullable',
            'message' => 'nullable',
            'trading_brand_id' => 'nullable',
            'recive_other_brands' => 'nullable',
            'marketplace_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $is_valid = isValidCard($request['my_card_id']);
        if (!is_object($is_valid)) {
            if ($is_valid == 0) {
                return response()->json(['status' => 400, 'message' => __('stringMessage.card_validation')]);
            }
        }
        $card = Card::where('id', $request->my_card_id)->where('user_id', auth()->user()->id)->first();
        $marketplace = Marketplace::where(['id' => $request->marketplace_id, 'listing_type' => 'trade', 'status' => 'pending_live', 'user_id' => auth()->user()->id])->first();
        if (isset($marketplace)) {
            $marketplace_card = Marketplacecards::where(['marketplace_id' => $marketplace->id, 'active' => 1])->first();
            if (isset($marketplace_card)) {
                $cardData = [];
                $message = isset($request->message) ? $request->message : '';
                $cardData['card_id'] = $request['my_card_id'];
                $cardData['brand_id'] = $card->brand_id;
                if (isset($request['trading_brand_id'])) {
                    $cardData['trading_brand_id'] = $request['trading_brand_id'];
                }
                if (isset($request['trading_amount'])) {
                    $cardData['trading_amount'] = $request['trading_amount'];
                }
                if (isset($request['recive_other_brands']) && isset($request['trading_brand_id'])) {
                    $cardData['recive_other_brands'] = $request['recive_other_brands'];
                }
                if (!isset($request['recive_other_brands']) && !isset($request['trading_brand_id'])) {
                    $cardData['recive_other_brands'] = 1;
                }
                $update = Marketplacecards::where(['marketplace_id' => $marketplace->id, 'active' => 1])->update($cardData);
                $updateMarketplace = Marketplace::where(['id' => $request->marketplace_id, 'user_id' => auth()->user()->id])->update(['selling_amount' => $request->trading_amount, 'message' => $message]);
                if (isset($update) && isset($updateMarketplace)) {
                    return response()->json(['status' => 200, 'message' => __('stringMessage.marketplace_card_edited')]);
                }
            }
        }
    }

    public function cardUpdateInAuctionMarketplace(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'minbid' => 'required|int',
            'card_id' => 'required',
            'auction_endtime' => 'required',
            'reserved_price' => 'nullable',
            'marketplace_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $marketplace = Marketplace::where(['id' => $request->marketplace_id, 'listing_type' => 'trade', 'status' => 'pending_live', 'user_id' => auth()->user()->id])->first();
        $is_valid = isValidCard($request['card_id']);
        if (!is_object($is_valid)) {
            if ($is_valid == 0) {
                return response()->json(['status' => 400, 'message' => __('stringMessage.card_validation')]);
            }
        }
        $card = Card::where('id', $request->card_id)->where('user_id', auth()->user()->id)->first();
        if (isset($marketplace)) {
            $marketplace_card = Marketplacecards::where(['marketplace_id' => $marketplace->id, 'active' => 1])->first();
            if (isset($marketplace_card)) {
                $cardData = [];
                $cardData['card_id'] = $request->card_id;
                $cardData['brand_id'] = $card->brand_id;
                $update = Marketplacecards::where(['marketplace_id' => $marketplace->id, 'active' => 1])->update($cardData);

                $data['selling_amount'] = $request->reserved_price;
                $data['bidding_expiry'] = $request->auction_endtime;
                $data['minbid'] = $request->minbid;

                $updateMarketplace = Marketplace::where(['id' => $request->marketplace_id, 'user_id' => auth()->user()->id])->update($data);
                if (isset($update) && isset($updateMarketplace)) {
                    return response()->json(['status' => 200, 'message' => __('stringMessage.marketplace_card_edited')]);
                }
            }
        }
    }

    //  get marketplace cards
    public function getMarketplaceByBrand(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand_id' => 'required',
            'minprice' => 'nullable',
            'maxprice' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $brand = Brand::where('id', $request['brand_id'])->first();

        $cards =  Marketplace::query();


        if (isset($request['listing_type'])) {
            $cards->whereIn('marketplace.listing_type', $request['listing_type']);
        }
        if (isset($request['minprice'])) {
            $cards->where('selling_amount', '>=', $request['minprice']);
        }
        if (isset($request['maxprice'])) {
            $cards->where('selling_amount', '<=', $request['maxprice']);
        }

        $cards = $cards->where('status', 'active')->whereHas('cards', function ($query) use ($request) {
            $query->where('brand_id', $request->brand_id);
        })->with(['cards.card', 'cards.trading_brand'])->get()->map(function ($marketplace) {
            $data =  [
                "marketplace_id" => $marketplace->id,
                "card_balance" => $marketplace->cards->sum('card.value'),
                "listing_type" => $marketplace->listing_type,
                "selling_amount" => $marketplace->selling_amount,
                "seller_name" => $marketplace->seller->name
            ];

            if ($marketplace->listing_type == 'trade') {

                if ($marketplace->cards->pluck('trading_brand.name')[0] == null) {
                    $data = array_merge($data, [
                        "exchange_card_data" => []
                    ]);
                } else {
                    $data = array_merge($data, [
                        "exchange_card_data" => $marketplace->cards->pluck('trading_brand.name')
                    ]);
                }
            }

            return $data;
        });

        return response()->json([
            'status' => 200,
            'data' => [
                'brand' => $brand,
                'cards' => $cards
            ],
            'message' => __('stringMessage.card_retrived')
        ]);
    }

    public function getMarketplaceCardById(Request $request, Marketplace $marketplace)
    {

        if ($marketplace->status == 'inactive') {
            return response()->json([
                'status' => 422,
                'message' => 'Inactive listing'
            ]);
        }

        $marketplace = $marketplace->load(['cards.card', 'offer_trades.offer_details', 'biddings']);
        $marketplace->is_owner = false;
        $marketplace_cards = $marketplace->cards->map(function ($marketplace_card) {
            // $cards = $marketplace_card->card->makeVisible([]);
            $cards = $marketplace_card->card;
            $brand = $marketplace_card->card->brand;

            // dd($marketplace_card->trading_brand);
            unset($marketplace_card->card);
            array_merge($marketplace_card->toArray(), $brand->toArray());
            $trading_brand = $marketplace_card->trading_brand;
            return array_merge($marketplace_card->toArray(), $cards->toArray());
        });
        $marketplace->is_owner = false;
        if (Auth::guard('api')->check()) {
            if (auth('api')->user()->id == $marketplace->user_id) {
                $marketplace->is_owner = true;
                $marketplace_trades = $marketplace->offer_trades->map(function ($offer_trade) {
                    $offer_details = $offer_trade->offer_details->map(function ($offer_detail) {
                        return array_merge($offer_detail->toArray(), [
                            'card' => $offer_detail->card()->with('brand')->first(),
                            // 'brand' => $offer_detail->card->brand
                        ]);
                    });
                    unset($offer_trade->offer_details);
                    $offer_trade->offer_details = $offer_details;
                    return array_merge($offer_trade->toArray(), [
                        'offered_user_name' => $offer_trade->user->name
                    ]);
                });
            } else {
                // if (auth('api')->user()->id == $marketplace->buyer_id) {  // anuradha-todo  -- filter with only buyer offer
                $marketplace_trades = $marketplace->offer_trades->filter(function ($offer_trade) {
                    return auth('api')->user()->id == $offer_trade->user_id_of_offer;
                })->map(function ($offer_trade) {
                    $offer_details = $offer_trade->offer_details->map(function ($offer_detail) {
                        return array_merge($offer_detail->toArray(), [
                            'card' => $offer_detail->card()->with('brand')->first(),
                            // 'brand' => $offer_detail->card->brand
                        ]);
                    });
                    unset($offer_trade->offer_details);
                    $offer_trade->offer_details = $offer_details;
                    return array_merge($offer_trade->toArray(), [
                        'offered_user_name' => $offer_trade->user->name
                    ]);
                });
                // } else {
                //     $marketplace_trades = [];
                // }
            }
        } else {
            $marketplace_trades = [];  //
        }

        $marketplace_bidding = $marketplace->biddings->map(function ($bidding) {
            $star = '';
            for ($i = 1; $i < strlen($bidding->user->name) - 1; $i++) {
                $star = $star . '*';
            }
            return array_merge($bidding->toArray(), [
                'bidding_user_name' => $star . substr($bidding->user->name, -2),
                // 'bidding_user_name' => $bidding->user->name
            ]);
        });
        if (count($marketplace_trades) > 0) {
            $marketplace->offers = $marketplace_trades;
        } else {
            $marketplace->offers = null;
        }
        unset($marketplace->offer_trades);
        unset($marketplace->cards);
        $is_seller = 0;
        $line1_header = '';
        if (Auth::guard('api')->check()) {
            if ($marketplace->user_id == auth('api')->user()->id) {
                $is_seller = 1;
            }
        }
        $marketplace->cards = $marketplace_cards;
        $marketplace->seller = $marketplace->seller;

        if ($marketplace->status == 'active') {
            return response()->json([
                'status' => 200,
                'data' => $marketplace,
                'message' => __('stringMessage.card_retrived'),
                'is_seller' => $is_seller,
                'line1_header' => $line1_header,
            ]);
        } else {
            if (Auth::guard('api')->check()) {
                if ($marketplace->status == 'pending_live') {
                    if (auth('api')->user()->id == $marketplace->user_id) {
                        return response()->json([
                            'status' => 200,
                            'data' => $marketplace,
                            'edit_time_remainging' => now()->diffInMinutes($marketplace->created_at),
                            'cta' => 'button',
                            'text' => 'edit',
                            'status_background_color' => '',
                            'status_text_color' => '',
                            'message' => __('stringMessage.card_retrived'),
                            'is_seller' => $is_seller,
                            'line1_header' => $line1_header,
                        ]);
                    }
                }
                if ($marketplace->status == 'hold') {
                    if (auth('api')->user()->id == $marketplace->user_id) {
                        if ($marketplace->listing_type == 'sell') {
                            return response()->json([
                                'status' => 200,
                                'data' => $marketplace,
                                'message' => __('stringMessage.card_retrived'),
                                'is_seller' => $is_seller,
                                'line1_header' => $line1_header,
                                'line1' => 'Please confirm your purchase works before funds are released to the seller.',
                                'line1_text_color' => '#00192F',
                                'line2' => now()->diffInDays($marketplace->created_at) . ' days left before funds are released to the seller',
                                'line2_text_color' => '#FF8000',
                                'link' => 'purchase_info',
                                'button1' => 'Confirm Success',
                                'button1_text_color' => '#00192F;',
                                'button1_bg_color' => '#2EDCAD;',
                                'button2' => 'Raise a dispute',
                                'button2_text_color' => '#EE6055;',
                                'button2_bg_color' => '#FFEFD3;',
                            ]);
                        }
                        if ($marketplace->listing_type == 'trade') {
                            return response()->json([
                                'status' => 200,
                                'data' => $marketplace,
                                'message' => __('stringMessage.card_retrived'),
                                'is_seller' => $is_seller,
                                'line1_header' => $line1_header,
                                'line1' => '',
                                'line2' => '',
                                'link' => 'purchase_info',
                                'button1' => 'Accept',
                                'button1_text_color' => '#00192F;',
                                'button1_bg_color' => '#2EDCAD;',
                                'button2' => 'Reject',
                                'button2_text_color' => '#EE6055;',
                                'button2_bg_color' => '#FFEFD3;',
                            ]);
                        }
                    } else {
                        if (auth('api')->user()->id == $marketplace->buyer_id) {
                            if ($marketplace->listing_type == 'sell') {
                                return response()->json([
                                    'status' => 200,
                                    'data' => $marketplace,
                                    'message' => __('stringMessage.card_retrived'),
                                    'is_seller' => $is_seller,
                                    'line1_header' => $line1_header,
                                    'line1' => 'Please confirm your purchase works before funds are released to the seller.',
                                    'line1_text_color' => '#00192F',
                                    'line2' => now()->diffInDays($marketplace->created_at) . ' days left before funds are released to the seller',
                                    'line2_text_color' => '#FF8000',
                                    'link' => 'purchase_info',
                                    'button1' => 'Accept',
                                    'button1_text_color' => '#00192F;',
                                    'button1_bg_color' => '#2EDCAD;',
                                    'button2' => 'Reject',
                                    'button2_text_color' => '#EE6055;',
                                    'button2_bg_color' => '#FFEFD3;',
                                ]);
                            }
                            if ($marketplace->listing_type == 'trade') {
                                return response()->json([
                                    'status' => 200,
                                    'data' => $marketplace,
                                    'message' => __('stringMessage.card_retrived'),
                                    'is_seller' => $is_seller,
                                    'line1_header' => $line1_header,
                                    'line1' => 'Please confirm your purchase works before funds are released to the seller.',
                                    'line2' => now()->diffInDays($marketplace->created_at) . ' days left before funds are released to the seller',
                                    'link' => 'purchase_info',
                                    'button1' => 'Accept',
                                    'button1_text_color' => '#00192F;',
                                    'button1_bg_color' => '#2EDCAD;',
                                    'button2' => 'Reject',
                                    'button2_text_color' => '#EE6055;',
                                    'button2_bg_color' => '#FFEFD3;',
                                ]);
                            }
                        }
                    }
                }
                if ($marketplace->status == 'dispute') {
                    if (auth('api')->user()->id == $marketplace->user_id) {
                        return response()->json([
                            'status' => 200,
                            'data' => $marketplace,
                            'message' => __('stringMessage.card_retrived'),
                            'is_seller' => $is_seller,
                            'line1_header' => $line1_header,
                            'line1' => 'You made a complaint which is under review',
                            'line1_text_color' => '#FF8000',
                            'line2' => 'COMPLAINT: ' . $marketplace->dispute_message,
                            'line2_text_color' => '#00192F',
                            'line3' => "We'll follow up with you via email and let you know in-app about your next steps",
                            'line3_text_color' => '#00192F',
                            'link' => 'purchase_info',
                        ]);
                    } else {
                        if (auth('api')->user()->id == $marketplace->buyer_id) {
                            return response()->json([
                                'status' => 200,
                                'data' => $marketplace,
                                'message' => __('stringMessage.card_retrived'),
                                'is_seller' => $is_seller,
                                'line1_header' => $line1_header,
                                'line1' => 'You made a complaint which is under review',
                                'line1_text_color' => '#FF8000',
                                'line2' => 'COMPLAINT: ' . $marketplace->dispute_message,
                                'line2_text_color' => '#00192F',
                                'line3' => "We'll follow up with you via email and let you know in-app about your next steps",
                                'line3_text_color' => '#00192F',
                                'link' => 'purchase_info',
                            ]);
                        }
                    }
                }
                if ($marketplace->status == 'dispute_completed') {
                    $activity = Activity::where('marketplace_id', $marketplace->id)->where('activity_type', 'dispute_completed')->first();
                    $status = '';
                    $line1 = '';
                    $line2 = '';
                    $line3 = '';
                    $link = "";
                    if (auth('api')->user()->id == $marketplace->user_id) {
                        if ($activity->status == 'rejected') {
                            $status = 'Complaint rejected';
                            $line1 = 'Your sale was accepted';
                            $line2 = 'COMPLAINT: ' . $marketplace->dispute_message;
                            $line3 = 'Upon investigation, we found that the ' . $marketplace->admin_reason;
                            $link = "";
                        }
                        if ($activity->status == 'accepted') {
                            $status = 'Complaint approved';
                            $line1 = 'Your sale was rejected';
                            $line2 = 'COMPLAINT: ' . $marketplace->dispute_message;
                            $line3 = 'Upon investigation, we found that the ' . $marketplace->admin_reason;
                            $link = "";


                            // $marketplace = Marketplace::where(['id' => $request['marketplace_id'], 'status' => 'active'])->first();

                            // $walletData = [];
                            // $walletData['activity_id'] = $activity->id;
                            // $walletData['marketplace_id'] = $marketplace->id;
                            // $walletData['transaction_type'] = 'add_cash';
                            // $walletData['amount'] = $marketplace->selling_amount;
                            // $activity['marketplace_owner_id'] = $marketplace->user_id;
                            // $walletData['from_user'] = $admin_user->id;
                            // $walletData['to_user'] = $marketplace->user_id;
                            // $walletData['status'] = 'completed';
                            // WalletTransaction::create($walletData);
                            // if (!empty($marketplace->intent_id)) {
                            //     $stripe = new \Stripe\StripeClient('sk_test_51KK7DeK0KZjvxE0zs0ma4VBzghTusnc8aZ9Pb4bkqTDQ4zq2LoWGTAFcYptnYOXqiTENrOYQwZeoGzKKeOJpKS2d007f1PGAxc');

                            //     $stripe->refunds->create(['payment_intent' => $marketplace->intent_id]);
                            // }
                        }
                        return response()->json([
                            'status' => 200,
                            'data' => $marketplace,
                            'message' => __('stringMessage.card_retrived'),
                            'is_seller' => $is_seller,
                            'line1_header' => $line1_header,
                            'line1' => $line1,
                            'line1_text_color' => '#FF8000',
                            'line2' => $line2,
                            'line2_text_color' => '#00192F',
                            'line3' => $line3,
                            'line3_text_color' => '#00192F',
                            'link' => $link,
                        ]);
                    } else {
                        if (auth('api')->user()->id == $marketplace->buyer_id) {
                            if ($activity->status == 'rejected') {
                                $status = 'Complaint rejected';
                                $line1 = 'Your complaint was rejected';
                                $line2 = 'COMPLAINT: ' . $marketplace->dispute_message;
                                $line3 = 'Upon investigation, we found that the ' . $marketplace->admin_reason;
                                $link = "purchase_info";
                            }
                            if ($activity->status == 'accepted') {
                                $status = 'Complaint approved';
                                $line1 = 'Your complaint was accepted';
                                $line2 = 'COMPLAINT: ' . $marketplace->dispute_message;
                                $line3 = 'Upon investigation, we found that the ' . $marketplace->admin_reason;
                                $link = "";
                            }
                            return response()->json([
                                'status' => 200,
                                'data' => $marketplace,
                                'message' => __('stringMessage.card_retrived'),
                                'is_seller' => $is_seller,
                                'line1_header' => $line1_header,
                                'line1' => $line1,
                                'line1_text_color' => '#FF8000',
                                'line2' => $line2,
                                'line2_text_color' => '#00192F',
                                'line3' => $line3,
                                'line3_text_color' => '#00192F',
                                'link' => $link,
                            ]);
                        }
                    }
                }
                if ($marketplace->status == 'completed') {
                    if (auth('api')->user()->id == $marketplace->user_id) {
                        return response()->json([
                            'status' => 200,
                            'data' => $marketplace,
                            'message' => __('stringMessage.card_retrived'),
                            'is_seller' => $is_seller,
                            'line1_header' => $line1_header,
                            'line1' => 'Your sale was completed',
                            'line2' => '',
                            'link' => 'view_all_cards',
                            'button' => 'card_details'
                        ]);
                    } else {
                        if (auth('api')->user()->id == $marketplace->buyer_id) {
                            return response()->json([
                                'status' => 200,
                                'data' => $marketplace,
                                'message' => __('stringMessage.card_retrived'),
                                'is_seller' => $is_seller,
                                'line1_header' => $line1_header,
                                'line1' => 'This card was purchased on',
                                'line2' => 'value of gift card',
                                'link' => 'view_all_cards',
                                'button' => 'card_details'
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function purchase(Request $request)
    {

        // print_r($request);die;
        $validator = Validator::make($request->all(), [
            'marketplace_id' => 'required',
            'listing_type' => ["required", "max:255", "regex:(sell|trade|auction)"],
            'card_id' => 'required_if:listing_type,trade',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $marketplace_exist = Marketplace::where(['id' => $request['marketplace_id'], 'status' => 'active'])->first();
        if ($marketplace_exist) {
            if ($request->listing_type == 'sell') {
                $marketplaceSell = Marketplace::where(['id' => $request['marketplace_id'], 'listing_type' => 'sell', 'status' => 'active'])->first();
                $user = User::where('id', auth()->user()->id)->first();
                if ($marketplaceSell->selling_amount > $user->balance) {
                    return response()->json(['status' => 200, 'allowPurchase' => false, 'message' => __('stringMessage.wallet_balance_min')]);
                }
                $card_details = Marketplacecards::where('marketplace_id', $marketplaceSell->id)->select('card_id')->first();
                $card_update = Card::where('id', $card_details->card_id)->update(['user_id' => auth()->user()->id]);
                if (!$card_update) {
                    return response()->json(['status' => 400,  'message' => __('stringMessage.something_wrong')]);
                }

                $Login_user = User::where('id', auth()->user()->id)->first();
                $esrow_account = User::where('id', config('app.ADMIN_ID'))->first();
                // $transfer = $Login_user->transfer($marketplace_user, 0);
                $transfer = $Login_user->transfer($esrow_account, $marketplaceSell->selling_amount);



                $marketplaceSell->buyer_id = auth()->user()->id;
                $marketplaceSell->save();
                $card_data = Card::where('id', $card_details->card_id)->first();

                $activity = [];
                $activity['action_user_id'] = auth()->user()->id;
                $activity['reciver_user_id'] = $marketplaceSell->user_id;
                $activity['activity_type'] = 'purchase';
                $activity['marketplace_id'] = $marketplaceSell->id;
                $activity['brand_id'] = $card_data->brand_id;
                $activity['marketplace_owner_id'] = $marketplaceSell->user_id;
                $activity['amount'] = $marketplaceSell->selling_amount;
                $activity['status'] = 'hold';
                $activity_first = Activity::create($activity);

                $activityData = [];
                $activityData['action_user_id'] = auth()->user()->id;
                $activityData['reciver_user_id'] = auth()->user()->id;
                $activityData['activity_type'] = 'purchase';
                $activityData['marketplace_id'] = $marketplaceSell->id;
                $activityData['brand_id'] = $card_data->brand_id;
                $activityData['marketplace_owner_id'] = $marketplaceSell->user_id;
                $activityData['amount'] = $marketplaceSell->selling_amount;
                $activityData['status'] = 'hold';
                Activity::create($activityData);

                if (isset($transfer)) {
                    $walletData = [];
                    $walletData['activity_id'] = $activity_first->id;
                    $walletData['marketplace_id'] = $marketplaceSell->id;
                    $walletData['transaction_type'] = 'transfer';
                    $walletData['amount'] = $marketplaceSell->selling_amount;
                    $walletData['from_user'] = auth()->user()->id;
                    $walletData['to_user'] = $esrow_account->id;
                    $walletData['status']  = 'completed';
                    WalletTransaction::create($walletData);
                }

                $is_update = Marketplace::where(['id' => $request['marketplace_id'], 'listing_type' => 'sell'])->update(['status' => 'hold']);
                return response()->json(['status' => 200, 'message' => __('stringMessage.card_purchased')]);
            }
            if ($request->listing_type == 'trade') {
                $marketplaceTrade = Marketplace::where(['id' => $request['marketplace_id'], 'listing_type' => 'trade', 'status' => 'active'])->first();
                if (!isset($marketplaceTrade)) {
                    return response()->json(['status' => 400, 'message' => __('stringMessage.trade_offer_request_failed')]);
                }
                $trades = DB::table('offer_trades')
                    ->join('offer_details', 'offer_details.offer_trade_id', '=', 'offer_trades.id')
                    ->where('offer_trades.user_id_of_offer', '=', auth()->user()->id)
                    ->where('offer_details.card_id', '=', $request->card_id)
                    ->first();

                if (isset($trades)) {
                    return response()->json(['status' => 400, 'message' => __('stringMessage.trade_offer_request_failed')]);
                }
                $marketplace_card = Marketplacecards::where('marketplace_id', $marketplaceTrade->id)->first();

                $is_valid = isValidCard($request['card_id']);
                if (!is_object($is_valid)) {
                    if ($is_valid == 0) {
                        return response()->json(['status' => 401, 'message' => __('stringMessage.card_validation')]);
                    }
                    if ($is_valid == 2) {
                        return response()->json(['status' => 470, 'message' => __('stringMessage.card_exist_in_marketplace')]);
                    }
                }
                if ($marketplace_card->recive_other_brands == 0) {
                    $offered_card_brand = Card::where('id', $request['card_id'])->select('brand_id')->first();
                    if ($offered_card_brand->brand_id != $marketplace_card->trading_brand_id) {
                        return response()->json(['status' => 471, 'message' => __('stringMessage.trade_offer_brand_different')]);
                    }
                }
                if (isset($marketplace_card->trading_amount)) {
                    $offered_card_brand = Card::where('id', $request['card_id'])->first();
                    if ($offered_card_brand->value < $marketplace_card->trading_amount) {
                        return response()->json(['status' => 472, 'message' => __('stringMessage.trade_offer_amount_min')]);
                    }
                }
                $data = [];
                $data['marketplace_id'] = $request['marketplace_id'];
                $data['user_id_of_offer'] = auth()->user()->id;
                $data['status'] = 'pending';
                $trade = OfferTrades::create($data);
                if (isset($trade)) {
                    $detail = [];
                    $detail['offer_trade_id'] = $trade->id;
                    $detail['card_id'] = $request['card_id'];
                    $details = OfferDetails::create($detail);
                }

                $card_data = Card::where('id', $marketplace_card->card_id)->first();

                $activity = [];
                $activity['action_user_id'] = auth()->user()->id;
                $activity['reciver_user_id'] = $marketplaceTrade->user_id;
                $activity['activity_type'] = 'place_trade_offer';
                $activity['marketplace_id'] = $marketplaceTrade->id;
                $activity['brand_id'] = $card_data->brand_id;
                $activity['marketplace_owner_id'] = $marketplaceTrade->user_id;
                $activity['amount'] = $marketplaceTrade->selling_amount;
                $activity['offered_brand_id'] = $is_valid->brand_id;
                $activity['status'] = 'hold';
                Activity::create($activity);

                $activityData = [];
                $activityData['action_user_id'] = auth()->user()->id;
                $activityData['reciver_user_id'] = auth()->user()->id;
                $activityData['activity_type'] = 'place_trade_offer';
                $activityData['marketplace_id'] = $marketplaceTrade->id;
                $activityData['marketplace_owner_id'] = $marketplaceTrade->user_id;
                $activityData['brand_id'] = $card_data->brand_id;
                $activityData['amount'] = $marketplaceTrade->selling_amount;
                $activityData['offered_brand_id'] = $is_valid->brand_id;
                $activityData['status'] = 'hold';
                Activity::create($activityData);

                if (App::environment('production')) {
                    $email = 'anuradha@skryptech.com';
                    $mailData = [
                        'subject' => 'Trade offer',
                        'title' => 'Trade offer',
                        'body' => "You have received a trade offer for your " . $marketplaceTrade->selling_amount . " " . $marketplace_card->trading_brand->name . " gift card in exchange for a " . $marketplaceTrade->selling_amount . " " . $is_valid->brand->name . " gift card! Please login to your GiftPass account to accept/decline this offer.",
                    ];
                    // Mail::to($email)->send(new welcomeMail($mailData));

                    Event::dispatch(new SendMail(auth()->user()->id, $mailData));
                }
                return response()->json(['status' => 200, 'message' => __('stringMessage.trade_offer_request_successfully')]);
            }
        }
        // if ($request->listing_type == 'auction') {
        //     $marketplaceAuction = Marketplace::where(['id' => $request['marketplace_id'], 'listing_type' => 'auction', 'status' => 'auction_timeup'])->first();
        //     $bidding = Bidding::where('marketplace_id', $request->marketplace_id)->where('user_id', auth()->user()->id)->where('wining_datetime', '!=', null)->where('payment_status', 'pending-payment')->first();
        //     if (!isset($bidding) || !isset($marketplaceAuction)) {
        //         return response()->json(['status' => 400,  'message' => __('stringMessage.something_wrong')]);
        //     }

        //     $user = User::where('id', auth()->user()->id)->first();
        //     if ($bidding->bidding_amount > $user->balance) {
        //         return response()->json(['status' => 200, 'allowPurchase' => false, 'message' => __('stringMessage.wallet_balance_min')]);
        //     }
        //     $card_details = Marketplacecards::where('marketplace_id', $marketplaceAuction->id)->select('card_id')->first();
        //     $card_update = Card::where('id', $card_details->card_id)->update(['user_id' => auth()->user()->id]);
        //     if (!$card_update) {
        //         return response()->json(['status' => 400,  'message' => __('stringMessage.something_wrong')]);
        //     }

        //     $Login_user = User::where('id', auth()->user()->id)->first();
        //     $esrow_account = User::where('id', config('app.ADMIN_ID'))->first();
        //     // $transfer = $Login_user->transfer($esrow_account, 0);
        //     $transfer = $Login_user->transfer($esrow_account, $bidding->bidding_amount);


        //     $marketplaceAuction->buyer_id = auth()->user()->id;
        //     $marketplaceAuction->save();
        //     $card_data = Card::where('id', $card_details->card_id)->first();

        //     $activity = [];
        //     $activity['action_user_id'] = auth()->user()->id;
        //     $activity['reciver_user_id'] = $marketplaceAuction->user_id;
        //     $activity['activity_type'] = 'purchase';
        //     $activity['marketplace_id'] = $marketplaceAuction->id;
        //     $activity['brand_id'] = $card_data->brand_id;
        //     $activity['marketplace_owner_id'] = $marketplaceAuction->user_id;
        //     $activity['amount'] = $bidding->bidding_amount;
        //     $activity['status'] = 'hold';
        //     $activity_first = Activity::create($activity);

        //     $activityData = [];
        //     $activityData['action_user_id'] = auth()->user()->id;
        //     $activityData['reciver_user_id'] = auth()->user()->id;
        //     $activityData['activity_type'] = 'purchase';
        //     $activityData['marketplace_id'] = $marketplaceAuction->id;
        //     $activityData['brand_id'] = $card_data->brand_id;
        //     $activityData['marketplace_owner_id'] = $marketplaceAuction->user_id;
        //     $activityData['amount'] = $bidding->bidding_amount;
        //     $activityData['status'] = 'hold';
        //     Activity::create($activityData);

        //     if (isset($transfer)) {
        //         $walletData = [];
        //         $walletData['activity_id'] = $activity_first->id;
        //         $walletData['marketplace_id'] = $marketplaceAuction->id;
        //         $walletData['transaction_type'] = 'transfer';
        //         $walletData['amount'] = $bidding->bidding_amount;
        //         $walletData['from_user'] = auth()->user()->id;
        //         $walletData['to_user'] = $esrow_account->id;
        //         $walletData['status']  = 'completed';
        //         WalletTransaction::create($walletData);
        //     }

        //     $is_update = Marketplace::where(['id' => $request['marketplace_id'], 'listing_type' => 'auction'])->update(['status' => 'hold']);
        //     return response()->json(['status' => 200, 'message' => __('stringMessage.card_purchased')]);
        // }
    }

    public function purchaseConfirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'marketplace_id' => 'required',
        ]);
        $payment_by_wallet = $request->payment_by_wallet;
        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('id', auth()->user()->id)->first();
        $marketplace_card = Marketplacecards::where('marketplace_id', $request['marketplace_id'])->first();
        $marketplace = Marketplace::where('id', $request['marketplace_id'])->first();
        $card = Card::where('id', $marketplace_card->card_id)->first();

        $data = [];
        $data['card_balance'] = $card->value;
        $data['price'] = $marketplace->selling_amount;
        $data['wallet_balance'] = $user->balance;
        $data['marketplace_id'] = $request['marketplace_id'];
        $data['card_id'] = $card->id;

        // anuradha-todo balance pay == selling amount - wallet balance....
        //call helper
        $payment_obj =  paymentIntent($request['marketplace_id'], null, $payment_by_wallet, $user->balance);

        return response()->json(['status' => 200, 'data' => $data, 'allowPurchase' => true, 'wallet_payment' => $payment_obj->wallet_amount, 'stripe_amount' => $payment_obj->stripe_amount, 'stripe_token' => $payment_obj->stripe_token]);
    }
    public function stripeWebhook(Request $request)
    {
        require '../vendor/autoload.php';

        // This is a public sample test API key.
        // Don’t submit any personally identifiable information in requests made with this key.
        // Sign in to see your own test API key embedded in code samples.
        \Stripe\Stripe::setApiKey('sk_test_tR3PYbcVNZZ796tH88S4VQ2u');
        // Replace this endpoint secret with your endpoint's unique secret
        // If you are testing with the CLI, find the secret by running 'stripe listen'
        // If you are using an endpoint defined with the API or dashboard, look in your webhook settings
        // at https://dashboard.stripe.com/webhooks
        $endpoint_secret = 'whsec_...';

        $payload = @file_get_contents('http://127.0.0.1:8000/api/v1/marketplace/webhook');
        $event = null;

        try {
            $event = \Stripe\Event::constructFrom(
                json_decode($payload, true)
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            echo '⚠️  Webhook error while parsing basic request.';
            http_response_code(400);
            exit();
        }
        if ($endpoint_secret) {
            // Only verify the event if there is an endpoint secret defined
            // Otherwise use the basic decoded event
            $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sig_header,
                    $endpoint_secret
                );
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                // Invalid signature
                echo '⚠️  Webhook error while validating signature.';
                http_response_code(400);
                exit();
            }
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object; // contains a \Stripe\PaymentIntent
                // Then define and call a method to handle the successful payment intent.
                // handlePaymentIntentSucceeded($paymentIntent);
                break;
            case 'payment_method.attached':
                $paymentMethod = $event->data->object; // contains a \Stripe\PaymentMethod
                // Then define and call a method to handle the successful attachment of a PaymentMethod.
                // handlePaymentMethodAttached($paymentMethod);
                break;
            default:
                // Unexpected event type
                error_log('Received unknown event type');
        }

        http_response_code(200);
    }

    public function webhook(Request $request)
    {
        return response()->json(['status' => 200, 'message' => 'success']);
    }
    public function changeTradeOfferStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offer_id' => 'required',
            'marketplace_id' => 'required',
            'status' => ["required", "max:255", "regex:(accepted|rejected)"],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $card = Marketplace::where('id', $request['marketplace_id'])->first();
        if ($card->user_id == auth()->user()->id) {
            $offer_update = OfferTrades::where(['id' => $request['offer_id'], 'marketplace_id' => $request['marketplace_id'], 'status' => 'pending'])->update(['status' => $request['status']]);
            // if ($offer_update == 0) {
            //     return response()->json(['status' => 200, 'message' => __('stringMessage.something_wrong')]);
            // }
            $offer_trade = OfferTrades::where(['id' => $request['offer_id'], 'marketplace_id' => $request['marketplace_id']])->first();
            $marketplace_card = Marketplacecards::where('marketplace_id', $request['marketplace_id'])->select('card_id')->first();
            $marketplace_card_data = Card::where('id', $marketplace_card->card_id)->first();
            $offer_detail = OfferDetails::where('offer_trade_id', $request['offer_id'])->select('card_id')->first();
            if ($request->status == 'accepted') {
                $other_cards = OfferTrades::where(['marketplace_id' => $request['marketplace_id']])->where('id', '!=', $request->offer_id)->update(['status' => 'rejected']);
                $owner_card = Card::where(['id' => $marketplace_card->card_id, 'user_id' => auth()->user()->id])->update(['user_id' => $offer_trade->user_id_of_offer]);
                $offered_card = Card::where(['id' => $offer_detail->card_id])->update(['user_id' => auth()->user()->id]);
            }
            $card->update(['buyer_id' => $request->status == 'accepted' ? $offer_trade->user_id_of_offer : null, 'status' => $request->status == 'accepted' ? 'hold' : $card->status]);
            $offered_brand = Card::where(['id' => $offer_detail->card_id])->first();
            $activity = [];
            $activity['action_user_id'] = auth()->user()->id;
            $activity['reciver_user_id'] = $offer_trade->user_id_of_offer;
            $activity['activity_type'] = $request->status == 'accepted' ? 'trade_offer_accepted' : 'trade_offer_rejected';
            $activity['marketplace_id'] = $card->id;
            $activity['brand_id'] = $marketplace_card_data->brand_id;
            $activity['marketplace_owner_id'] = $card->user_id;
            $activity['amount'] = $card->selling_amount;
            $activity['offered_brand_id'] = $offered_brand->brand_id;
            Activity::create($activity);

            $activity = [];
            $activity['action_user_id'] = auth()->user()->id;
            $activity['reciver_user_id'] = auth()->user()->id;
            $activity['activity_type'] = $request->status == 'accepted' ? 'trade_offer_accepted' : 'trade_offer_rejected';
            $activity['marketplace_id'] = $card->id;
            $activity['brand_id'] = $marketplace_card_data->brand_id;
            $activity['marketplace_owner_id'] = $card->user_id;
            $activity['amount'] = $card->selling_amount;
            $activity['offered_brand_id'] = $offered_brand->brand_id;
            Activity::create($activity);
            if (App::environment('production')) {
                if ($request->status == 'accepted') {
                    $email = 'anuradha@skryptech.com';
                    $mailData = [
                        'subject' => 'Sold a marketplace listed gift card',
                        'title' => 'Sold a marketplace listed gift card',
                        'body' => "Congratulations! Your trade offer for your " . $offer_detail->card->brand->name . " gift card in exchange for a " . $marketplace_card->trading_brand->name . " gift card has been accepted! ",
                    ];
                    // Mail::to($email)->send(new welcomeMail($mailData));
                    Event::dispatch(new SendMail(auth()->user()->id, $mailData));
                } else {
                    $email = 'anuradha@skryptech.com';
                    $mailData = [
                        'subject' => 'Sold a marketplace listed gift card',
                        'title' => 'Sold a marketplace listed gift card',
                        'body' => "Your trade offer for your " . $offer_detail->card->brand->name . " gift card in exchange for a " . $marketplace_card->trading_brand->name . " gift card has been declined. Give it another go!",
                    ];
                    // Mail::to($email)->send(new welcomeMail($mailData));

                    Event::dispatch(new SendMail(auth()->user()->id, $mailData));
                }
            }
            if ($owner_card && $offered_card) {
                return response()->json(['status' => 200, 'message' => 'Status Updated Successfully']);
            }
        }
        return response()->json(['status' => 400, 'message' => 'you are not owner of card']);
    }

    public function acceptTradeCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'marketplace_id' => 'required',
            'status' => ["required", "max:255", "regex:(accepted|rejected)"],
            'dispute_message' => 'required_if:status,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $marketplace = Marketplace::where('id', $request['marketplace_id'])->where('status', 'hold')->first();
        // dd($marketplace);
        if (($marketplace->user_id == auth()->user()->id) || ($marketplace->buyer_id == auth()->user()->id)) {
            if (isset($marketplace)) {
                $is_update = 0;

                if ($request->status == 'accepted') {
                    if ((auth()->user()->id == $marketplace->buyer_id) && $request->status == 'accepted') {
                        $offer = OfferTrades::where('marketplace_id', $request['marketplace_id'])->where('user_id_of_offer', $marketplace->buyer_id)->where('status', 'accepted')->orWhere('status', 'accepted_by_seller')->first();
                        $offer_detail = OfferDetails::where('offer_trade_id', $offer->id)->select('card_id')->first();
                        $offered_brand = Card::where(['id' => $offer_detail->card_id])->first();
                        if ($offer->status == 'accepted') {
                            $offer->update(['status' => 'accepted_by_buyer']);
                            $is_update = 1;
                            // activity
                            $activity = [];
                            $activity['action_user_id'] = auth()->user()->id;
                            $activity['reciver_user_id'] = $marketplace->buyer_id;
                            $activity['brand_id'] = $marketplace->cards[0]->brand_id;
                            $activity['activity_type'] = 'accepted_by_buyer';
                            $activity['marketplace_id'] = $marketplace->id;
                            $activity['marketplace_owner_id'] = $marketplace->user_id;
                            $activity['amount'] = $marketplace->selling_amount;
                            $activity['offered_brand_id'] = $offered_brand->brand_id;
                            Activity::create($activity);

                            $activity = [];
                            $activity['action_user_id'] = auth()->user()->id;
                            $activity['reciver_user_id'] = $marketplace->user_id;
                            $activity['brand_id'] = $marketplace->cards[0]->brand_id;
                            $activity['activity_type'] = 'accepted_by_buyer';
                            $activity['marketplace_id'] = $marketplace->id;
                            $activity['marketplace_owner_id'] = $marketplace->user_id;
                            $activity['amount'] = $marketplace->selling_amount;
                            $activity['offered_brand_id'] = $offered_brand->brand_id;
                            Activity::create($activity);
                        }
                        if ($offer->status == 'accepted_by_seller') {
                            $offer->update(['status' => 'accepted_by_both']);
                            $marketplace->update(['status' => 'completed']);
                            $activity_update = Activity::where('marketplace_id', $marketplace->id)->where('activity_type', 'accepted_by_buyer')->orWhere('activity_type', 'accepted_by_seller')->update(['active' => 0]);
                            $is_update = 1;
                            // activity
                            $activity = [];
                            $activity['action_user_id'] = auth()->user()->id;
                            $activity['reciver_user_id'] = $marketplace->buyer_id;
                            $activity['brand_id'] = $marketplace->cards[0]->brand_id;
                            $activity['activity_type'] = 'accepted_by_both';
                            $activity['marketplace_id'] = $marketplace->id;
                            $activity['marketplace_owner_id'] = $marketplace->user_id;
                            $activity['amount'] = $marketplace->selling_amount;
                            $activity['offered_brand_id'] = $offered_brand->brand_id;
                            Activity::create($activity);

                            $activity = [];
                            $activity['action_user_id'] = auth()->user()->id;
                            $activity['reciver_user_id'] = $marketplace->user_id;
                            $activity['brand_id'] = $marketplace->cards[0]->brand_id;
                            $activity['activity_type'] = 'accepted_by_both';
                            $activity['marketplace_id'] = $marketplace->id;
                            $activity['marketplace_owner_id'] = $marketplace->user_id;
                            $activity['amount'] = $marketplace->selling_amount;
                            $activity['offered_brand_id'] = $offered_brand->brand_id;
                            Activity::create($activity);
                        }
                    }
                    if (auth()->user()->id == $marketplace->user_id && $request->status == 'accepted') {
                        $offer = OfferTrades::where('marketplace_id', $request['marketplace_id'])->where('user_id_of_offer', $marketplace->buyer_id)->where('status', 'accepted')->orWhere('status', 'accepted_by_buyer')->first();
                        $offer_detail = OfferDetails::where('offer_trade_id', $offer->id)->select('card_id')->first();
                        $offered_brand = Card::where(['id' => $offer_detail->card_id])->first();
                        if ($offer->status == 'accepted') {
                            $offer->update(['status' => 'accepted_by_seller']);
                            $is_update = 1;
                            // activity
                            $activity = [];
                            $activity['action_user_id'] = auth()->user()->id;
                            $activity['reciver_user_id'] = $marketplace->buyer_id;
                            $activity['brand_id'] = $marketplace->cards[0]->brand_id;
                            $activity['activity_type'] = 'accepted_by_seller';
                            $activity['marketplace_id'] = $marketplace->id;
                            $activity['marketplace_owner_id'] = $marketplace->user_id;
                            $activity['amount'] = $marketplace->selling_amount;
                            $activity['offered_brand_id'] = $offered_brand->brand_id;
                            Activity::create($activity);

                            $activity = [];
                            $activity['action_user_id'] = auth()->user()->id;
                            $activity['reciver_user_id'] = $marketplace->user_id;
                            $activity['brand_id'] = $marketplace->cards[0]->brand_id;
                            $activity['activity_type'] = 'accepted_by_seller';
                            $activity['marketplace_id'] = $marketplace->id;
                            $activity['marketplace_owner_id'] = $marketplace->user_id;
                            $activity['amount'] = $marketplace->selling_amount;
                            $activity['offered_brand_id'] = $offered_brand->brand_id;
                            Activity::create($activity);
                        }
                        if ($offer->status == 'accepted_by_buyer') {
                            $offer->update(['status' => 'accepted_by_both']);
                            $activity_update = Activity::where('marketplace_id', $marketplace->id)->where('activity_type', 'accepted_by_buyer')->orWhere('activity_type', 'accepted_by_seller')->update(['active' => 0]);
                            $marketplace->update(['status' => 'completed']);
                            $is_update = 1;
                            // activity
                            $activity = [];
                            $activity['action_user_id'] = auth()->user()->id;
                            $activity['reciver_user_id'] = $marketplace->user_id;
                            $activity['brand_id'] = $marketplace->cards[0]->brand_id;
                            $activity['activity_type'] = 'accepted_by_both';
                            $activity['marketplace_id'] = $marketplace->id;
                            $activity['marketplace_owner_id'] = $marketplace->user_id;
                            $activity['amount'] = $marketplace->selling_amount;
                            $activity['offered_brand_id'] = $offered_brand->brand_id;
                            Activity::create($activity);

                            $activity = [];
                            $activity['action_user_id'] = auth()->user()->id;
                            $activity['reciver_user_id'] = $marketplace->buyer_id;
                            $activity['brand_id'] = $marketplace->cards[0]->brand_id;
                            $activity['activity_type'] = 'accepted_by_both';
                            $activity['marketplace_id'] = $marketplace->id;
                            $activity['marketplace_owner_id'] = $marketplace->user_id;
                            $activity['offered_brand_id'] = $offered_brand->brand_id;
                            $activity['amount'] = $marketplace->selling_amount;
                            Activity::create($activity);
                        }
                    }
                }
                if ($request->status == 'rejected') {
                    if (auth()->user()->id == $marketplace->user_id || auth()->user()->id == $marketplace->buyer_id) {
                        $offer = OfferTrades::where('marketplace_id', $request['marketplace_id'])->where('user_id_of_offer', $marketplace->buyer_id)->where('status', 'accepted')->orWhere('status', 'accepted_by_seller')->orWhere('status', 'accepted_by_buyer')->first();
                        $offer_detail = OfferDetails::where('offer_trade_id', $offer->id)->select('card_id')->first();
                        $offered_brand = Card::where(['id' => $offer_detail->card_id])->first();
                        Marketplace::where(['id' => $request->marketplace_id, 'status' => 'hold'])->update(['status' => 'dispute', 'dispute_message' => $request->dispute_message]);

                        // activity
                        $activity = [];
                        $activity['action_user_id'] = auth()->user()->id;
                        $activity['reciver_user_id'] = $marketplace->buyer_id;
                        $activity['brand_id'] = $marketplace->cards[0]->brand_id;
                        $activity['activity_type'] = 'dispute';
                        $activity['marketplace_id'] = $marketplace->id;
                        $activity['marketplace_owner_id'] = $marketplace->user_id;
                        $activity['amount'] = $marketplace->selling_amount;
                        $activity['offered_brand_id'] = $offered_brand->brand_id;
                        Activity::create($activity);

                        $activity = [];
                        $activity['action_user_id'] = auth()->user()->id;
                        $activity['reciver_user_id'] = $marketplace->user_id;
                        $activity['brand_id'] = $marketplace->cards[0]->brand_id;
                        $activity['activity_type'] = 'dispute';
                        $activity['marketplace_id'] = $marketplace->id;
                        $activity['marketplace_owner_id'] = $marketplace->user_id;
                        $activity['amount'] = $marketplace->selling_amount;
                        $activity['offered_brand_id'] = $offered_brand->brand_id;
                        Activity::create($activity);

                        return response()->json(['status' => 200, 'message' => __('stringMessage.dispute_requested')]);
                    }
                }
                if ($is_update == 1) {
                    return response()->json(['status' => 200, 'message' => __('stringMessage.status_changed')]);
                }
                if ($is_update == 0) {
                    return response()->json(['status' => 401, 'message' => __('stringMessage.something_wrong')]);
                }
            }
        }
        return response()->json(['status' => 401, 'message' => __('stringMessage.something_wrong')]);
    }

    public function placeBid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'marketplace_id' => 'required',
            'bidding_amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $marketplace = Marketplace::where(['id' => $request['marketplace_id'], 'listing_type' => 'auction', 'status' => 'active'])->first();
        if (isset($marketplace)) {
            $marketplace_card = Marketplacecards::where('marketplace_id', $marketplace->id)->first();
            $existing_bids = Bidding::where('marketplace_id', $request['marketplace_id'])->where('active', 1)->orderBy('bidding_amount', 'desc')->get();
            $data = [];
            $data['marketplace_id'] = $request['marketplace_id'];
            $data['bidding_amount'] = $request['bidding_amount'];
            $data['user_id'] = auth()->user()->id;
            $data['active'] = 1;
            if (count($existing_bids) <= 0) {
                // check with minbid
                if ($marketplace->minbid > $request->bidding_amount) {
                    return response()->json(['status' => 401, 'message' => __('stringMessage.bid_amount_validation')]);
                }
                $bid = Bidding::create($data);
                // activity
                $activity = [];
                $activity['action_user_id'] = auth()->user()->id;
                $activity['reciver_user_id'] = auth()->user()->id;
                $activity['brand_id'] = $marketplace_card->brand_id;
                $activity['activity_type'] = 'bid_placed';
                $activity['marketplace_id'] = $data['marketplace_id'];
                $activity['marketplace_owner_id'] = $marketplace->user_id;
                $activity['amount'] = $data['bidding_amount'];
                Activity::create($activity);

                $activity = [];
                $activity['action_user_id'] = auth()->user()->id;
                $activity['reciver_user_id'] = $marketplace->user_id;
                $activity['brand_id'] = $marketplace_card->brand_id;
                $activity['activity_type'] = 'bid_placed';
                $activity['marketplace_id'] = $marketplace->id;
                $activity['marketplace_owner_id'] = $marketplace->user_id;
                $activity['amount'] = $data['bidding_amount'];
                Activity::create($activity);
                // end activity

                return response()->json(['status' => 200, 'message' => __('stringMessage.bid_placed')]);
            } else {
                if ($existing_bids[0]->bidding_amount < $request['bidding_amount']) {
                    // $bidding = Bidding::where(['marketplace_id' => $request['marketplace_id'], 'user_id' => auth()->user()->id])->first();
                    if ($existing_bids[0]->user_id == auth()->user()->id) {
                        $bidding = Bidding::where(['id' => $existing_bids[0]->id, 'marketplace_id' => $request['marketplace_id'], 'user_id' => auth()->user()->id])->update(['bidding_amount' => $request['bidding_amount']]);

                        // activity
                        $activity = [];
                        $activity['action_user_id'] = auth()->user()->id;
                        $activity['reciver_user_id'] = auth()->user()->id;
                        $activity['brand_id'] = $marketplace_card->brand_id;
                        $activity['activity_type'] = 'bid_placed';
                        $activity['marketplace_id'] = $data['marketplace_id'];
                        $activity['marketplace_owner_id'] = $marketplace->user_id;
                        $activity['amount'] = $data['bidding_amount'];
                        Activity::create($activity);

                        $activity = [];
                        $activity['action_user_id'] = auth()->user()->id;
                        $activity['reciver_user_id'] = $marketplace->user_id;
                        $activity['brand_id'] = $marketplace_card->brand_id;
                        $activity['activity_type'] = 'bid_placed';
                        $activity['marketplace_id'] = $marketplace->id;
                        $activity['marketplace_owner_id'] = $marketplace->user_id;
                        $activity['amount'] = $data['bidding_amount'];
                        Activity::create($activity);

                        if (count($existing_bids) >= 1) {
                            // $activity = [];
                            // $activity['action_user_id'] = auth()->user()->id;
                            // $activity['reciver_user_id'] = $existing_bids[0]->user_id;
                            // $activity['brand_id'] = $marketplace_card->brand_id;
                            // $activity['activity_type'] = 'out_bid';
                            // $activity['marketplace_id'] = $marketplace->id;
                            // $activity['marketplace_owner_id'] = $marketplace->user_id;
                            // $activity['amount'] = $data['bidding_amount'];
                            // Activity::create($activity);
                        }
                        return response()->json(['status' => 200, 'message' => __('stringMessage.bid_placed')]);
                    } else {
                        $bid = Bidding::create($data);

                        // activity
                        $activity = [];
                        $activity['action_user_id'] = auth()->user()->id;
                        $activity['reciver_user_id'] = auth()->user()->id;
                        $activity['brand_id'] = $marketplace_card->brand_id;
                        $activity['activity_type'] = 'bid_placed';
                        $activity['marketplace_id'] = $data['marketplace_id'];
                        $activity['marketplace_owner_id'] = $marketplace->user_id;
                        $activity['amount'] = $data['bidding_amount'];
                        Activity::create($activity);

                        $activity = [];
                        $activity['action_user_id'] = auth()->user()->id;
                        $activity['reciver_user_id'] = $marketplace->user_id;;
                        $activity['brand_id'] = $marketplace_card->brand_id;
                        $activity['activity_type'] = 'bid_placed';
                        $activity['marketplace_id'] = $marketplace->id;
                        $activity['marketplace_owner_id'] = $marketplace->user_id;
                        $activity['amount'] = $data['bidding_amount'];
                        Activity::create($activity);
                        if (count($existing_bids) >= 1) {
                            $activity = [];
                            $activity['action_user_id'] = auth()->user()->id;
                            $activity['reciver_user_id'] = $existing_bids[0]->user_id;
                            $activity['brand_id'] = $marketplace_card->brand_id;
                            $activity['activity_type'] = 'out_bid';
                            $activity['marketplace_id'] = $marketplace->id;
                            $activity['marketplace_owner_id'] = $marketplace->user_id;
                            $activity['amount'] = $data['bidding_amount'];
                            Activity::create($activity);
                        }
                        return response()->json(['status' => 200, 'message' => __('stringMessage.bid_placed')]);
                    }
                } else {
                    return response()->json(['status' => 402, 'message' => __('stringMessage.bid_amount_validation')]);
                }
            }
        }

        return response()->json(['status' => 400, 'message' => 'Card not found']);
    }

    public function getBids(Request $request,  Marketplace $marketplace)
    {

        $marketplace = $marketplace->load(['biddings']);
        $marketplace_bidding = $marketplace->biddings->map(function ($bidding) {
            return array_merge($bidding->toArray(), [
                'bidding_user_name' => $bidding->user->name
            ]);
        });
        return response()->json([
            'status' => 200,
            'data' => $marketplace_bidding,
            'message' => __('stringMessage.card_retrived')
        ]);
    }

    public function selectBidWinner(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'marketplace_id' => 'required',
            'bid_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $is_valid = Marketplace::where('id', $request->marketplace_id)->where('user_id', auth()->user()->id)->first();
        if (!isset($is_valid)) {
            return response()->json(['status' => 400, 'message' => __('stringMessage.card_validation')]);
        }
        $bidding = Bidding::where('marketplace_id', $request->marketplace_id)->where('id', $request->bid_id)->update(['payment_status' => 'pending-payment', 'wining_datetime' => now()]);

        // buyer id update in marketplace

        if ($bidding) {
            return response()->json([
                'status' => 200,
                'message' => __('stringMessage.bid_winner_selected')
            ]);
        }
    }

    public function withdrawRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'marketplace_id' => 'required',
            'withdraw_message' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $marketplace = Marketplace::where('id', $request->marketplace_id)->first();
        if (isset($marketplace)) {
            // if ($marketplace->listing_type == 'auction') {
            //     if (!isset($request->bid_id)) {
            //         return response()->json(['status' => 400, 'message' => 'Bid_id require']);
            //     }
            //     $bid = Bidding::where(['marketplace_id' => $request->marketplace_id, 'id' => $request->bid_id, 'user_id' => auth()->user()->id, 'active' => 1])->where('created_at', '<=', now()->subMinutes(config('app.withdraw_bid_min')))->where('payment_status', null)->first();
            //     if (!isset($bid)) {
            //         return response()->json(['status' => 400, 'message' => __('stringMessage.bid_not_found')]);
            //     }
            //     $is_update = $bid->update(['active' => 0, 'withdraw_datetime' => now()]);
            //     if (isset($is_update)) {
            //         return response()->json(['status' => 200, 'message' => __('stringMessage.bid_withdraw')]);
            //     }
            // }
            if ($marketplace->listing_type == 'trade') {
                if (!isset($request->offer_id)) {
                    return response()->json(['status' => 400, 'message' => 'offer_id require']);
                }
                $offer = OfferTrades::where(['marketplace_id' => $request->marketplace_id, 'id' => $request->offer_id, 'user_id_of_offer' => auth()->user()->id, 'active' => 1])->first();
                if (!isset($offer)) {
                    return response()->json(['status' => 400, 'message' => __('stringMessage.trade_offer_not_found')]);
                }
                $is_update = OfferTrades::where(['marketplace_id' => $request->marketplace_id, 'id' => $request->offer_id, 'user_id_of_offer' => auth()->user()->id])->update(['withdraw_message' => $request->withdraw_message, 'withdraw_datetime' => now(), 'active' => 0]);

                if (isset($is_update)) {
                    $activity = [];
                    $activity['action_user_id'] = auth()->user()->id;
                    $activity['reciver_user_id'] = $marketplace->user_id;
                    $activity['activity_type'] = 'trade_offer_withdraw';
                    $activity['marketplace_id'] = $marketplace->id;
                    $activity['brand_id'] = $marketplace->cards[0]->brand_id;
                    $activity['marketplace_owner_id'] = $marketplace->user_id;
                    $activity['amount'] = $marketplace->selling_amount;
                    // $activity['status'] = 'trade_offer_withdraw';
                    Activity::create($activity);

                    $activityData = [];
                    $activityData['action_user_id'] = auth()->user()->id;
                    $activityData['reciver_user_id'] = $offer->user_id_of_offer;
                    $activityData['activity_type'] = 'trade_offer_withdraw';
                    $activityData['marketplace_id'] = $marketplace->id;
                    $activityData['brand_id'] = $marketplace->cards[0]->brand_id;
                    $activityData['marketplace_owner_id'] = $marketplace->user_id;
                    $activityData['amount'] = $marketplace->selling_amount;
                    // $activityData['status'] = 'trade_offer_withdraw';
                    Activity::create($activityData);

                    return response()->json(['status' => 200, 'message' => __('stringMessage.trade_offer_withdraw')]);
                }
            }
        }
        return response()->json(['status' => 400, 'message' => __('stringMessage.marketplace_not_found')]);
    }

    public function removeCardFromMarktplace(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'marketplace_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $marketplace = Marketplace::where(['id' => $request['marketplace_id'], 'status' => 'pending_live', 'user_id' => auth()->user()->id])->first();
        if (isset($marketplace)) {
            $marketplace_card = Marketplacecards::where(['marketplace_id' => $marketplace->id, 'active' => 1])->first();
            if (isset($marketplace_card)) {
                $update = Marketplacecards::where(['marketplace_id' => $marketplace->id, 'active' => 1])->update(['active' => 0]);
                $updateMarketplace = Marketplace::where(['id' => $request['marketplace_id'], 'user_id' => auth()->user()->id])->update(['status' => 'inactive']);
                if (isset($update) && isset($updateMarketplace)) {
                    return response()->json(['status' => 200, 'message' => __('stringMessage.status_changed')]);
                }
            }
            return response()->json(['status' => 400, 'message' => __('stringMessage.something_wrong')]);
        } else {
            $marketplace_data = Marketplace::where(['id' => $request['marketplace_id'], 'status' => 'active', 'user_id' => auth()->user()->id])->first();

            if (isset($marketplace_data)) {
                if (isset($request->cancel_message)) {
                    return response()->json(['status' => 200, 'message' => 'cancel message required']);
                } else {
                    $marketplace_card = Marketplace::where(['id' => $request['marketplace_id'], 'user_id' => auth()->user()->id])->first();
                    $marketplace_card = Marketplacecards::where(['marketplace_id' => $marketplace_data->id, 'active' => 1])->first();
                    if (isset($marketplace_card)) {
                        $marketplace_data->cancel_request = true;
                        $marketplace_data->dispute_message = $request['cancel_message'];
                        $marketplace_data->status = 'cancel_requested';
                        $marketplace_data->save();
                        return response()->json(['status' => 200, 'message' => __('stringMessage.cancel_requested')]);
                    }
                }
            }
        }
    }

    public function giftCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_id' => 'required',
            'recipient' => 'required',
            'method_of_delivery' => ["required", "max:255", "regex:(email|sms)"],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $is_valid = isValidCard($request->card_id);
        if (!is_object($is_valid)) {
            if ($is_valid == 0) {
                return response()->json(['status' => 400, 'message' => __('stringMessage.card_validation')]);
            }
            if ($is_valid == 2) {
                return response()->json(['status' => Response::HTTP_UNPROCESSABLE_ENTITY, 'message' =>  __('stringMessage.card_exist_in_marketplace')]);
            }
        }

        $user = User::where('email_hash', hash_sha($request->recipient))
            ->orWhere('mobile_hash', hash_sha($request->recipient))->first();

        $data = [
            'user_id' => isset($user) ? $user->id : null,
            'sender_user_id' => auth()->user()->id,
            'method_of_delivery' => $request->method_of_delivery,
            'recipient' => $request->recipient,
            'message' => $request->message,
            'date_of_delivery' => now(),
            'card_id' => $request->card_id
        ];
        $card = $is_valid->load(['brand']);
        $gift = Gift::create($data);
        if (isset($gift)) {
            if (isset($user)) {
                $is_update = Card::where('id', $is_valid->id)->update(['user_id' => $user->id]);
            } else {
                $is_update = Card::where('id', $is_valid->id)->update(['active' => 0, 'user_id' => null]);
            }
            if (App::environment('production')) {
                // Reciever
                $email = 'anuradha@skryptech.com';
                $mailData = [
                    'subject' => 'Gifting a gift card',
                    'title' => 'Gifting a gift card',
                    'body' => "You have recieved a  " . $card->brand->name . " gift card from " . auth()->user()->username . " ! [Sender Message]",
                ];
                // Mail::to($email)->send(new welcomeMail($mailData));
                Event::dispatch(new SendMail(auth()->user()->id, $mailData));

                // Sender
                $email = 'anuradha@skryptech.com';
                $mailData = [
                    'subject' => 'Gifting a gift card',
                    'title' => 'Gifting a gift card',
                    'body' => "You have successfully sent " . $request->recipient . " a " . $card->brand->name . " gift card. You're so nice!",
                ];
                // Mail::to($email)->send(new welcomeMail($mailData));

                Event::dispatch(new SendMail(auth()->user()->id, $mailData));
            }

            $message = array('title' => "GiftCard", 'body' => "" . auth()->user()->username . " has sent you a gift card!");
            $this->notify(array($message, auth()->user()->id));
            return response()->json(['status' => 200, 'message' => 'Gifted successfully', 'card' => $card]);
        }
    }

    public function getMarketplaceStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'activity_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $activity_marketplace = Activity::where('id', $request->activity_id)->first();
        if (isset($activity_marketplace)) {
            $marketplace = Marketplace::where('id', $activity_marketplace->marketplace_id)->select('status')->first();
            return response()->json([
                'status' => 200,
                'data' => $marketplace,
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => "Not Found",
            ]);
        }
    }
}

function activity()
{
}
