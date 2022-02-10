<?php

namespace App\Domains\Activity\Http\Controllers\API;

use App\Domains\Activity\Models\Activity;
use App\Domains\Auth\Models\User;
use App\Domains\Card\Models\Brand;
use App\Domains\Marketplace\Models\Bidding;
use App\Domains\Marketplace\Models\Marketplace;
use App\Domains\Marketplace\Models\OfferTrades;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ActivityController extends Controller
{
    public function myActivity(Request $request, $type = null)
    {

        if (isset($type)) {
            $type = Str::lower($type);
            if (isset($type)) {
                if ($type == 'sales') {
                    $marketplace = Marketplace::where('listing_type', 'sell')->where('user_id', auth()->user()->id)->pluck('id')->toArray();
                }
                if ($type == "purchases") {
                    $marketplace = Marketplace::where('listing_type', 'sell')->where('buyer_id', auth()->user()->id)->pluck('id')->toArray();
                }
                if ($type == "trades") {
                    $marketplace = Marketplace::where('listing_type', 'trade')->where('user_id',auth()->user()->id)->pluck('id')->toArray();
                }
                $activity = Activity::where('reciver_user_id', auth()->user()->id)->where('active', 1)->whereIn('marketplace_id', $marketplace)->orderBy('id', 'desc')->get();
            }
        } else {
            $activity = Activity::where('reciver_user_id', auth()->user()->id)->where('active', 1)->orderBy('id', 'desc')->get();
        }
        
        $activity_data = [];
        $i = 0;
        foreach ($activity as $key => $value) {
           $data = activityMessage($value);
           $activity_data[$i] = $data;
           $i++ ;
        }
        return response()->json(['status' => 200, 'data' => $activity_data, 'message' => 'Activity retrived Successfully']);
    }
}
