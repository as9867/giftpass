<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Log;
use Str;
use Hash;
use Exception;
use App\Domains\Auth\Models\User;
use App\Domains\Auth\Models\WalletTransaction;
use App\Domains\Card\Models\Card;
use App\Domains\Marketplace\Models\Marketplace;
use App\Domains\Marketplace\Models\Marketplacecards;
use App\Domains\Activity\Models\Activity;

class PaymentCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:cron';

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
        $marketplace_data = Marketplace::where('status','=','hold')->where('created_at','<=',now()->subDays(config('app.payment')))->get();
        foreach ($marketplace_data as $key => $value) {
            $wallettransaction = WalletTransaction::where('marketplace_id',$value->id)->first();
            $admin_user = User::where('id', config('app.ADMIN_ID'))->first();
            $to_user = User::where('id', $value->user_id)->first();
            $admin_user->wallet->refreshBalance();  
            if($admin_user->balance >= $wallettransaction->amount){
                $transfer = $to_user->transfer($admin_user, $wallettransaction->amount);
            }
            if(isset($transfer)){
                $walletData = [];
                $walletData['activity_id'] = $wallettransaction->activity_id;
                $walletData['marketplace_id'] = $wallettransaction->marketplace_id;
                $walletData['transaction_type'] = 'transfer';
                $walletData['amount'] = $wallettransaction->selling_amount;
                $walletData['from_user'] = $admin_user->id;
                $walletData['to_user'] = $to_user->id;
                WalletTransaction::create($walletData);
            }
            $value->status = 'completed';
            $value->save();
        }
       } catch (\Throwable $th) {
           //throw $th;
       }
        
    }
}
