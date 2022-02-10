<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBiddingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('biddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')
                ->constrained('marketplace')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');    
            $table->string('bidding_amount', 512);
            $table->unsignedTinyInteger('active')->default(1); // 0-inactive 1-active
            $table->enum('payment_status', ['pending-payment', 'peyment-completed', 'past_due'])->nullable()->default(null); 
            $table->text('withdraw_message')->nullable();
            $table->timestamp('withdraw_datetime')->nullable();
            $table->timestamp('wining_datetime')->nullable();
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
        Schema::dropIfExists('biddings');
    }
}
