<?php

namespace App\Domains\Marketplace\Http\Controllers\Backend;

use App\Domains\Activity\Models\Activity;
use DB;
use App\Domains\Auth\Models\User;
use App\Http\Controllers\Controller;
use App\Domains\Auth\Models\WalletTransaction;
use App\Domains\Card\Models\Card;
use App\Domains\Marketplace\Models\Bidding;
use App\Domains\Marketplace\Models\Marketplace;
use App\Domains\Marketplace\Models\OfferDetails;
use App\Domains\Marketplace\Models\OfferTrades;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function index()
    {
        return view('backend.marketplace.index');
    }

    public function show(Marketplace $marketplace)
    {
        return view('backend.marketplace.show', [
            'marketplace' => $marketplace
        ]);
    }

    public function dispute(Request $request)
    {
        $marketplace = Marketplace::where('id', $request->marketplace_id)->first();
        if ($request->status == 'accept') {
            // reverse the transactions
            if ($marketplace->listing_type == 'trade') {
                $trade_offer = OfferTrades::where('marketplace_id', $marketplace->id)->where('user_id_of_offer', $marketplace->buyer_id)->first();
                $trade_detail = OfferDetails::where('offer_trade_id', $trade_offer->id)->first();
                $first_card = Card::where('user_id', $marketplace->user_id)->where('id', $trade_detail->card_id)->first();
                $second_card = Card::where('user_id', $marketplace->buyer_id)->where('id', $marketplace->cards[0]->card_id)->first();
                // dd($first_card,$second_card);
                $first_card->update(['user_id' => $marketplace->buyer_id]);
                $second_card->update(['user_id' => $marketplace->user_id]);

                $marketplace->update(['status' => 'dispute_completed', 'admin_reason' => $request->admin_reason]);

                $activity = [];
                $activity['action_user_id'] = auth()->user()->id;
                $activity['reciver_user_id'] = $marketplace->user_id;
                $activity['activity_type'] = 'dispute_completed';
                $activity['marketplace_id'] = $marketplace->id;
                $activity['brand_id'] = $marketplace->cards[0]->brand_id;
                $activity['marketplace_owner_id'] = $marketplace->user_id;
                $activity['amount'] = $marketplace->selling_amount;
                $activity['status'] = 'accepted';
                $activity['offered_brand_id'] = $trade_detail->card->brand_id;
                $activity_first = Activity::create($activity);

                $activityData = [];
                $activityData['action_user_id'] = auth()->user()->id;
                $activityData['reciver_user_id'] = $marketplace->buyer_id;
                $activityData['activity_type'] = 'dispute_completed';
                $activityData['marketplace_id'] = $marketplace->id;
                $activityData['brand_id'] = $marketplace->cards[0]->brand_id;
                $activityData['marketplace_owner_id'] = $marketplace->user_id;
                $activityData['amount'] = $marketplace->selling_amount;
                $activityData['status'] = 'accepted';
                $activityData['offered_brand_id'] = $trade_detail->card->brand_id;
                Activity::create($activityData);
                $mailData = [
                    'subject' => 'Marketplace Listing',
                    'title' => 'Marketplace Listing',
                    'body' => "Your request to remove your gift card from the Market place has been approved.",
                ];
                // Mail::to($email)->send(new welcomeMail($mailData));
                Event::dispatch(new SendMail(auth()->user()->id, $mailData));

                return redirect()->route('admin.marketplace.index')->withFlashSuccess('Listing reversed succesfully.');
            } else {
                $transaction = WalletTransaction::query()
                    ->where('marketplace_id', $marketplace->id)
                    ->first();

                if (!$transaction) {
                    // return
                }

                if ($transaction->status != 'completed') {
                    //
                }

                // Reverse amount
                $from_user = User::find($transaction->from_user);
                $admin_user = User::find(config('app.ADMIN_ID'));
                $transfer = $admin_user->transfer($from_user, $transaction->amount);

                $activity = [];
                $activity['action_user_id'] = auth()->user()->id;
                $activity['reciver_user_id'] = $marketplace->user_id;
                $activity['activity_type'] = 'dispute_completed';
                $activity['marketplace_id'] = $marketplace->id;
                $activity['brand_id'] = $marketplace->cards[0]->brand_id;
                $activity['marketplace_owner_id'] = $marketplace->user_id;
                $activity['amount'] = $marketplace->selling_amount;
                $activity['status'] = 'accepted';
                $activity_first = Activity::create($activity);

                $activityData = [];
                $activityData['action_user_id'] = auth()->user()->id;
                $activityData['reciver_user_id'] = $marketplace->buyer_id;
                $activityData['activity_type'] = 'dispute_completed';
                $activityData['marketplace_id'] = $marketplace->id;
                $activityData['brand_id'] = $marketplace->cards[0]->brand_id;
                $activityData['marketplace_owner_id'] = $marketplace->user_id;
                $activityData['amount'] = $marketplace->selling_amount;
                $activityData['status'] = 'accepted';
                Activity::create($activityData);

                if (isset($transfer)) {
                    $walletData = [];
                    $walletData['activity_id'] = $activity_first->id;
                    $walletData['marketplace_id'] = $marketplace->id;
                    $walletData['transaction_type'] = 'transfer';
                    $walletData['amount'] = $marketplace->selling_amount;
                    $walletData['from_user'] = $admin_user->id;
                    $walletData['to_user'] = $from_user->id;
                    $walletData['status']  = 'completed';
                    WalletTransaction::create($walletData);
                }

                // Reverse cards
                // $data = $marketplace->cards;
                Card::where('id', $marketplace->cards[0]->card_id)->update(['user_id' => $marketplace->user_id]);
            }
            $marketplace->update(['status' => 'dispute_completed', 'admin_reason' => $request->admin_reason]);

            return redirect()->route('admin.marketplace.index')->withFlashSuccess('Listing reversed succesfully.');
        }
        if ($request->status == 'reject') {
            if ($marketplace->listing_type == 'trade') {
                $trade_offer = OfferTrades::where('marketplace_id', $marketplace->id)->where('user_id_of_offer', $marketplace->buyer_id)->first();
                $trade_detail = OfferDetails::where('offer_trade_id', $trade_offer->id)->first();
                $activity['offered_brand_id'] = $trade_detail->card->brand_id;
                $activityData['offered_brand_id'] = $trade_detail->card->brand_id;
            }
            $activity = [];
            $activity['action_user_id'] = auth()->user()->id;
            $activity['reciver_user_id'] = $marketplace->user_id;
            $activity['activity_type'] = 'dispute_completed';
            $activity['marketplace_id'] = $marketplace->id;
            $activity['brand_id'] = $marketplace->cards[0]->brand_id;
            $activity['marketplace_owner_id'] = $marketplace->user_id;
            $activity['amount'] = $marketplace->selling_amount;
            $activity['status'] = 'rejected';
            $activity_first = Activity::create($activity);

            $activityData = [];
            $activityData['action_user_id'] = auth()->user()->id;
            $activityData['reciver_user_id'] = $marketplace->buyer_id;
            $activityData['activity_type'] = 'dispute_completed';
            $activityData['marketplace_id'] = $marketplace->id;
            $activityData['brand_id'] = $marketplace->cards[0]->brand_id;
            $activityData['marketplace_owner_id'] = $marketplace->user_id;
            $activityData['amount'] = $marketplace->selling_amount;
            $activityData['status'] = 'rejected';
            Activity::create($activityData);

            $marketplace->update(['status' => 'dispute_completed', 'admin_reason' => $request->admin_reason]);
            return redirect()->route('admin.marketplace.index')->withFlashSuccess('Dispute rejected succesfully.');
        }
    }

    public function reverse(Marketplace $marketplace)
    {
        return view('backend.marketplace.dispute', [
            'marketplace' => $marketplace
        ]);
    }

    public function tradeWithdraw(Request $request)
    {
        $bidding = OfferTrades::where('id', $request->bid)->update(['admin_reason' => $request->admin_reason, 'active' => 0]);
        if (isset($bidding)) {
            $marketplace_id = OfferTrades::where('id', $request->bid)->first();
            $marketplace = Marketplace::where('id', $marketplace_id->marketplace_id)->first();

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
            $activityData['reciver_user_id'] = $bidding->user_id_of_offer;
            $activityData['activity_type'] = 'trade_offer_withdraw';
            $activityData['marketplace_id'] = $marketplace->id;
            $activityData['brand_id'] = $marketplace->cards[0]->brand_id;
            $activityData['marketplace_owner_id'] = $marketplace->user_id;
            $activityData['amount'] = $marketplace->selling_amount;
            // $activityData['status'] = 'trade_offer_withdraw';
            Activity::create($activityData);

            return redirect()->to('/admin/marketplace')->withFlashSuccess(__('Bid Withdraw successfully.'));
        }
    }

    public function tradeShow(OfferTrades $offerTrades)
    {
        $marketplace = Marketplace::where('id', $offerTrades->marketplace_id)->first();
        return view('backend.marketplace.withdraw', [
            'bidding' => $offerTrades,
            'marketplace' => $marketplace
        ]);
    }

    public function offerStatus(Request $request)
    {
        $offerTrades = OfferTrades::where('id', $request->offer_id)->first();
        if ($offerTrades->active == 0) {
            OfferTrades::where('id', $offerTrades->id)->update(['active' => 1, 'admin_reason' => $request->admin_reason]);
        }
        if ($offerTrades->active == 1) {
            OfferTrades::where('id', $offerTrades->id)->update(['active' => 0, 'admin_reason' => $request->admin_reason]);
            // dd($offerTrades);
        }

        $marketplace = Marketplace::where('id', $offerTrades->marketplace_id)->first();

        $activity = [];
        $activity['action_user_id'] = auth()->user()->id;
        $activity['reciver_user_id'] = $marketplace->user_id;
        $activity['activity_type'] = 'trade_status_change';
        $activity['marketplace_id'] = $marketplace->id;
        $activity['brand_id'] = $marketplace->cards[0]->brand_id;
        $activity['marketplace_owner_id'] = $marketplace->user_id;
        $activity['amount'] = $marketplace->selling_amount;
        // $activity['status'] = 'trade_offer_withdraw';
        Activity::create($activity);

        $activityData = [];
        $activityData['action_user_id'] = auth()->user()->id;
        $activityData['reciver_user_id'] = $offerTrades->user_id_of_offer;
        $activityData['activity_type'] = 'trade_status_change';
        $activityData['marketplace_id'] = $marketplace->id;
        $activityData['brand_id'] = $marketplace->cards[0]->brand_id;
        $activityData['marketplace_owner_id'] = $marketplace->user_id;
        $activityData['amount'] = $marketplace->selling_amount;
        // $activityData['status'] = 'trade_offer_withdraw';
        Activity::create($activityData);

        return redirect()->back()->withFlashSuccess(__('Offer status changed successfully.'));
    }

    public function bidStatus(Request $request)
    {
        $bidding = Bidding::where('id', $request->bid_id)->withoutGlobalScope('active')->first();
        // dd($bidding);
        if ($bidding->active == 0) {
            $is_update = Bidding::where('id', $request->bid_id)->withoutGlobalScope('active')->update(['active' => 1, 'admin_reason' => $request->admin_reason]);
        }
        if ($bidding->active == 1) {
            Bidding::where('id', $request->bid_id)->withoutGlobalScope('active')->update(['active' => 0, 'admin_reason' => $request->admin_reason]);
        }

        $marketplace = Marketplace::where('id', $bidding->marketplace_id)->first();

        $activity = [];
        $activity['action_user_id'] = auth()->user()->id;
        $activity['reciver_user_id'] = $marketplace->user_id;
        $activity['activity_type'] = 'bid_status_change';
        $activity['marketplace_id'] = $marketplace->id;
        $activity['brand_id'] = $marketplace->cards[0]->brand_id;
        $activity['marketplace_owner_id'] = $marketplace->user_id;
        $activity['amount'] = $marketplace->selling_amount;
        // $activity['status'] = 'trade_offer_withdraw';
        Activity::create($activity);

        $activityData = [];
        $activityData['action_user_id'] = auth()->user()->id;
        $activityData['reciver_user_id'] = $bidding->user_id;
        $activityData['activity_type'] = 'bid_status_change';
        $activityData['marketplace_id'] = $marketplace->id;
        $activityData['brand_id'] = $marketplace->cards[0]->brand_id;
        $activityData['marketplace_owner_id'] = $marketplace->user_id;
        $activityData['amount'] = $marketplace->selling_amount;
        // $activityData['status'] = 'trade_offer_withdraw';
        Activity::create($activityData);

        return redirect()->back()->withFlashSuccess(__('Bidding status changed successfully.'));
    }
}
