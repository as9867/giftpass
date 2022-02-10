<?php

namespace App\Console\Commands;

use App\Domains\Activity\Models\Activity;
use App\Domains\Marketplace\Models\Bidding;
use App\Domains\Marketplace\Models\Marketplace;
use App\Domains\Marketplace\Models\Marketplacecards;
use Illuminate\Console\Command;
use App\Mail\welcomeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;

class LivecardsCronCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livecards:cron';

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
        try {
            $cards = Marketplace::where('status', 'pending_live')->where('created_at','<=',now()->subMinutes(config('app.live_cards')))->get();

            foreach ($cards as $key => $value) {
                $card_update = Marketplace::where('id', $value->id)->where('status', 'pending_live')->update(['status' => 'active']);
                $card = Marketplace::where('id', $value->id)->first();
                $marketplace_card_data = Marketplacecards::where('marketplace_id', $card->id)->first();
                $activity = [];
                $activity['action_user_id'] = $card->user_id;
                $activity['reciver_user_id'] = $card->user_id;
                $activity['brand_id'] = $marketplace_card_data->brand_id;
                $activity['activity_type'] = 'card_active';
                $activity['marketplace_id'] = $card->id;
                $activity['marketplace_owner_id'] = $card->user_id;
                $activity['amount'] = $card->selling_amount;
                Activity::create($activity);

                if (App::environment('production')) {
                    $email = 'anuradha@skryptech.com';
                    $mailData = [
                        'subject' => 'Listing a card on the marketplace',
                        'title' => 'Listing a card on the marketplace',
                        'body' => 'Hello @' . $card->seller->username. ', you have successfully added your' . $marketplace_card_data->card->value . $marketplace_card_data->card->brand->name . ' gift card to your GiftPass wallet. Happy Shopping!',
                    ];
                    Mail::to($email)->send(new welcomeMail($mailData));
                }
            }
        } catch (\Throwable $th) {
        }
    }
}
