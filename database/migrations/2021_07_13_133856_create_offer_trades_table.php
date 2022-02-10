<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOfferTradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offer_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')
                  ->constrained('marketplace')
                  ->onDelete('cascade');
            $table->foreignId('user_id_of_offer')
                  ->constrained()
                  ->onDelete('cascade');
            $table->text('withdraw_message')->nullable();     
            $table->timestamp('withdraw_datetime')->nullable(); 
            $table->enum('status',['pending','accepted','rejected'])->nullable();  
            $table->unsignedTinyInteger('active')->default(1); // 0-inactive 1-active               
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
        Schema::dropIfExists('offer_trades');
    }
}
