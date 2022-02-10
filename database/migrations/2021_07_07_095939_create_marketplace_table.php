<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketplaceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketplace', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
            $table->enum('listing_type',['sell', 'trade', 'auction'])->nullable();
            $table->double('selling_amount', 8, 2)->nullable();
            $table->dateTime('bidding_expiry')->nullable();
            $table->text('message')->nullable();
            $table->text('dispute_message')->nullable();
            $table->enum('status',['active','hold','dispute','dispute_completed','inactive','completed','pending_live','auction_timeup','cancel_requested'])->default('active');
            $table->double('minbid', 8, 2)->nullable();
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
        Schema::dropIfExists('marketplaces');
    }
}
