<?php

namespace App\Console\Commands;

use App\Domains\Activity\Models\Activity;
use App\Domains\Card\Models\Card;
use App\Domains\Marketplace\Models\Bidding;
use App\Domains\Marketplace\Models\Marketplace;
use App\Domains\Marketplace\Models\Marketplacecards;
use Illuminate\Console\Command;

class BidWinningCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bidwinning:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // anuradha-todo reserved price condition need to add
        // add in where
        // $marketplace_bidding_data = Marketplace::where('listing_type', 'auction')->where('bidding_expiry', '<=', now())->where('buyer_id', null)->get();
        $marketplace_bidding_data = Marketplace::where('listing_type', 'auction')->where('buyer_id', null)->get();
        foreach ($marketplace_bidding_data as $key => $value) {
            if (isset($value->selling_amount)) {
                $bidding_data = Bidding::where('marketplace_id', $value->id)->where('active', 1)->orderBy('bidding_amount', 'DESC')->where('bidding_amount', '>=', $value->selling_amount)->get();
            } else {
                $bidding_data = Bidding::where('marketplace_id', $value->id)->where('active', 1)->orderBy('bidding_amount', 'DESC')->get();
            }

            if (count($bidding_data) > 0) {
                $bid_winner = clone $bidding_data[0];
                $bid_winner->payment_status = 'pending-payment';
                $bid_winner->wining_datetime = now();
                $bid_winner->save();
                // brand and card data
                $marketplace_card = Marketplacecards::where('marketplace_id', $value->id)->first();
                // activity
                $activity = [];
                $activity['action_user_id'] = $bid_winner->user_id;
                $activity['reciver_user_id'] = $bid_winner->user_id;
                $activity['brand_id'] = $marketplace_card->brand_id;
                $activity['activity_type'] = 'bid_win';
                $activity['marketplace_id'] = $value->id;
                $activity['amount'] = $bid_winner->bidding_amount;
                $activity['marketplace_owner_id'] = $value->user_id;
                Activity::create($activity);

                $activity = [];
                $activity['action_user_id'] = $bid_winner->user_id;
                $activity['reciver_user_id'] = $value->user_id;
                $activity['brand_id'] = $marketplace_card->brand_id;
                $activity['activity_type'] = 'bid_win';
                $activity['marketplace_id'] = $value->id;
                $activity['amount'] = $bid_winner->bidding_amount;
                $activity['marketplace_owner_id'] = $value->user_id;
                Activity::create($activity);
                // end activity

                $is_update = Marketplace::where('id',$value->id)->update(['buyer_id' => $bid_winner->id, 'status' => 'auction_timeup']);

            }
        }

        // if past 7 days winner is not paying after 7 days buyer id = -1

        $bidding_pending = Bidding::where('active', 1)->where('wining_datetime', '<=', now()->subDays(config('app.bid_win_reasign')))->where('payment_status', 'pending-payment')->get();
        foreach ($bidding_pending as $key => $value) {
            // $value->update(['payment_status' => 'past_due']);
            $is_update = Bidding::where('id', $value->id)->update(['payment_status' => 'past_due']);
            // brand and card data
            $marketplace = Marketplace::where('id', $value->marketplace_id)->first();
            $isupdate = Marketplace::where('id', $value->marketplace_id)->update(['buyer_id' => -1]);
            $marketplace_card = Marketplacecards::where('marketplace_id', $value->marketplace_id)->first();

            if ($is_update) {
                // activity
                $activity = [];
                $activity['action_user_id'] = $marketplace->user_id;
                $activity['reciver_user_id'] = $marketplace->user_id;
                $activity['brand_id'] = $marketplace_card->brand_id;
                $activity['activity_type'] = 'past_due';
                $activity['marketplace_id'] = $marketplace->id;
                $activity['amount'] = $value->bidding_amount;
                $activity['marketplace_owner_id'] = $marketplace->user_id;
                Activity::create($activity);

                $activity = [];
                $activity['action_user_id'] = $marketplace->user_id;
                $activity['reciver_user_id'] = $value->user_id;
                $activity['brand_id'] = $marketplace_card->brand_id;
                $activity['activity_type'] = 'past_due';
                $activity['marketplace_id'] = $marketplace->id;
                $activity['amount'] = $value->bidding_amount;
                $activity['marketplace_owner_id'] = $marketplace->user_id;
                Activity::create($activity);
            }
            // end activity
        }

        // anuradha-todo iterate and save entry in activity table and also send message to owner choose winner 
    }
}
