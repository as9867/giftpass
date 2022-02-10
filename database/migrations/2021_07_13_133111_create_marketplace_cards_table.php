<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketplaceCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketplace_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')
                  ->constrained('marketplace')
                  ->onDelete('cascade');
            $table->foreignId('card_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->enum('type',['list','trade'])->nullable();  
            $table->foreignId('brand_id')->nullable()
                  ->constrained('brands')
                  ->onDelete('cascade');
            $table->double('trading_amount',8,2)->nullable(); 
            $table->unsignedTinyInteger('active')->default(1);     
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
        Schema::dropIfExists('marketplace_cards');
    }
}
