<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUseractivityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_activity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->enum('activity_type', [
                'add_card_for_sell', 'add_card_for_trade',
                'add_card_for_auction','card_active', 'list_card', 'add_cash', 'withdraw_cash', 'bid_placed', 'out_bid', 'top_bidder','bid_win','past_due',
                'place_trade_offer','hold', 'purchase','purchase_completed', 'gift', 'dispute', 'dispute_completed','trade_offer_accepted','trade_offer_rejected','accepted_by_buyer','accepted_by_both','accepted_by_seller','trade_offer_withdraw','trade_status_change','bid_status_change'])->nullable();
            $table->foreignId('marketplace_id')->nullable()
                ->constrained('marketplace')
                ->onDelete('cascade');
            $table->foreignId('reciver_user_id')  //reciver
                ->constrained('users')
                ->onDelete('cascade');
            $table->double('amount', 8, 2)->nullable();
            $table->foreignId('brand_id')
                ->constrained('brands')
                ->onDelete('cascade');
            $table->foreignId('marketplace_owner_id')->nullable()
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('action_by')->nullable()
                ->constrained('users')
                ->onDelete('cascade');   
            $table->unsignedTinyInteger('isread')->default(1);
            $table->enum('status', ['pending', 'hold', 'accepted', 'rejected','canceled','dispute'])->nullable(); //anuradha-todo merge status and activity_type  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_activity');
    }
}
