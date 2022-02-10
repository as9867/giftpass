<?php

namespace App\Domains\Card\Http\Controllers\API;

use DB;
use Illuminate\Http\Request;
use App\Domains\Card\Models\Card;
use App\Domains\Card\Models\Brand;
use App\Http\Controllers\Controller;
use App\Domains\Card\Models\Categories;
use App\Domains\Activity\Models\Activity;
use App\Domains\Marketplace\Models\Marketplace;
use App\Domains\Marketplace\Models\Marketplacecards;
use App\Domains\Marketplace\Models\OfferDetails;
use App\Domains\Marketplace\Models\OfferTrades;
use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Response;
use App\Mail\welcomeMail;
use Illuminate\Support\Facades\Mail;
use Event;

use App\Events\SendMail;

class CardsController extends Controller
{

    public function digitize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand_id' => 'required|int',
            'value' => 'required',
            'secret' => 'required_with:srno',
            'expiry' => 'nullable|date',
            'srno' => 'required_without:url',
            'url' => 'required_without:srno'
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data = [
            'user_id' => auth()->user()->id,
            'brand_id' => $request->brand_id,
            'value' => $request->value,
            'secret' => $request->secret,
            'expiry' => $request->expiry,
            'srno' => $request->srno,
            'url' => $request->url
        ];
        $isBrandExist = Brand::where('id', $data['brand_id'])->first();
        if (!isset($isBrandExist)) {
            return response()->json(['status' => 400, 'message' => 'Brand does not exist']);
        }
        $card = Card::create($data);

        $activity = [];
        $activity['action_user_id'] = auth()->user()->id;
        $activity['reciver_user_id'] = auth()->user()->id;
        $activity['brand_id'] = $card->brand_id;
        $activity['activity_type'] = 'list_card';
        $activity['amount'] = $card->value;
        Activity::create($activity);
        if (App::environment('production')) {
            $email = 'anuradha@skryptech.com';

            $mailData = [
                'subject' => 'Digitizing a card',
                'title' => 'Digitizing a card',
                'body' => 'Hello @' . auth()->user()->username . ', you have successfully added your' . $card->value . ' ' . $card->brand->name . ' gift card to your GiftPass wallet. Happy Shopping!',
            ];

            // Mail::to($email)->send(new welcomeMail($mailData));

            Event::dispatch(new SendMail(auth()->user()->id, $mailData));
        }

        return response()->json(['status' => 200, 'data' => $card, 'message' => 'Card saved successfully']);
    }

    public function deleteDigitize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_id' => 'required|int',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => implode(",", $validator->messages()->all()), 'status' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $card = Card::where('id', $request->card_id)->where('user_id', auth()->user()->id)->where(['active' => 1])->first();
        if (isset($card)) {
            Card::where('id', $request->card_id)->where('user_id', auth()->user()->id)->update(['active' => 0]);
            return response()->json(['status' => 200, 'message' => 'Card deleted successfully']);
        } else {
            return response()->json(['status' => 400, 'message' => 'Card not found']);
        }
    }

    public function getMyDigitizedCards(Request $request, $brand_id = null, $marketplace_id = null)
    {
        /** @var User */
        $user = auth()->user();
        $tradding_amount = null;
        $is_purchased = 0;
        if (isset($marketplace_id)) {
            $marketplace_data = Marketplace::where(['listing_type' => 'trade', 'id' => $marketplace_id])->first();
            if (isset($marketplace_data)) {
                $marketplace_card_data = Marketplacecards::where('marketplace_id', $marketplace_id)->first();
                if (isset($marketplace_card_data)) {
                    $tradding_amount = $marketplace_card_data->trading_amount;
                    if (isset($marketplace_card_data->trading_brand_id) && ($marketplace_card_data->recive_other_brands == 0)) {
                        $brand_id = $marketplace_card_data->trading_brand_id;
                        if ($marketplace_data->buyer_id == auth()->user()->id) {
                            $is_purchased = $marketplace_card_data->card_id;
                            // $data = DB::select("SELECT * , (CASE WHEN id=$marketplace_card_data->card_id THEN 0 WHEN id!=$marketplace_card_data->card_id THEN 1 END) as timestamp FROM cards ORDER BY timestamp asc");
                            // return ($data);
                        }
                    }
                }
            }
        }
        if (isset($brand_id) && ($brand_id != 0)) {
            $cards = $user->cards()->with('brand')->where('active', 1)->where('brand_id', $brand_id)->get();
        }
        if (!isset($cards)) {
            $cards = $user->cards()->with('brand')->get();
        }
        if ($is_purchased != 0) {
            $cards = $user->cards()->with('brand')->orderByRaw(DB::raw("FIELD(id, $is_purchased) desc"))
                ->get();
        }
        foreach ($cards as $key => $value) {
            $card_in_marketplace = Marketplacecards::where('card_id', $value->id)->first();
            $value->already_listed_status = false;
            if (isset($card_in_marketplace)) {
                $marketplace = Marketplace::where(['id' => $card_in_marketplace->marketplace_id, 'user_id' => auth()->user()->id])
                    ->whereIn('status', ['active', 'hold', 'dispute', 'pending_live'])
                    ->first();
                if ($marketplace) {
                    $value->already_listed_status = true;
                }
                // active hold dispute pending_live
            }
            $offer = OfferDetails::where('card_id', $value->id)->first();
            if (isset($offer)) {
                // marketplace -- active hold dispute 
                $trade =  OfferTrades::where(['id' => $offer->offer_trade_id])
                    ->whereIn('status', ['accepted', 'pending'])
                    ->first();
                if (isset($trade)) {
                    $value->already_listed_status = true;
                }
            }
            if ($tradding_amount != null) {
                if ($value->value < $tradding_amount) {
                    $value->already_listed_status = true; // this is hack done to show the card disable in the ui
                }
            }
            // offer
            // accepted null pending   true
        }
        $cards = $cards->sortBy('already_listed_status');
        $cards = $cards->values()->all();

        return response()->json(['status' => 200, 'data' => $cards, 'message' => 'data retrived successfully']);
    }

    public function getMyDigitizedBrands(Request $request)
    {
        /** @var User */
        $user = auth()->user();

        $brands = $user->cards()->whereHas('brand', function ($query) use ($request) {
            if (isset($request->category_id)) {
                $query->whereIn('category_id', $request->category_id);
            }
            if (isset($request->name)) {
                $query->where('name', 'LIKE', '%' . $request->name . '%');
            }
        })->with('brand')->get()->pluck('brand')->unique()->values();

        return response()->json(['status' => 200, 'data' => $brands, 'message' => 'data retrived successfully']);
    }

    public function getDigitizedCardByCardID(Request $request, $card_id)
    {
        /** @var User */
        $user = auth()->user();
        $cards = $user->cards()->where('id', $card_id)->with('brand')->first()->makeVisible(['secret', 'srno']);
        return response()->json(['status' => 200, 'data' => $cards, 'message' => 'data retrived successfully']);
    }

    public function getAllBrands(Request $request)
    {
        $brands = Brand::query();
        if (isset($request['name'])) {
            $brands = $brands->where('name', 'LIKE', '%' . $request['name'] . '%');
        }
        if (isset($request['category'])) {
            $brands = $brands->where('category_id', '=', $request['category']);
        }

        $brands = $brands->get();

        return response()->json(['status' => 200, 'data' => $brands, 'message' => 'data retrived successfully']);
    }

    public function getCategories(Request $request)
    {
        $categories = Categories::where('active', 1)->get();

        return response()->json(['status' => 200, 'data' => $categories, 'message' => 'data retrived successfully']);
    }

    public function getmarketplaceBrandByCategory(Request $request)
    {
        $cards =  Marketplace::query();
        if (isset($request['min_value'])) {
            $cards->where(['marketplace.selling_amount' => $request['min_value']]);
        }
        if (isset($request['max_value'])) {
            $cards->where(['marketplace.selling_amount' => $request['max_value']]);
        }
        if (isset($request->listing_type)) {
            $cards->whereIn('marketplace.listing_type', $request->listing_type);
        }

        $data = $cards->where('status', 'active')->with(['cards.card'])->get()->map(function ($marketplace) {
            return $marketplace->cards[0]->brand_id;
        });


        $brands = Brand::whereIn('id', $data);
        if (isset($request['category_id'])) {
            $brands = $brands->whereIn('category_id', $request['category_id']);
        }
        if (isset($request['name'])) {
            $brands = $brands->where('name', 'LIKE', '%' . $request['name'] . '%');
        }

        $brands = $brands->get();

        return response()->json(['status' => 200, 'data' => $brands, 'message' => 'data retrived successfully']);
    }
}
