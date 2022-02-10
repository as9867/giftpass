<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletstransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallets_transaction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')
                ->constrained('user_activity')
                ->onDelete('cascade');
            $table->foreignId('marketplace_id')
                  ->constrained('marketplace')
                  ->onDelete('cascade');       
            $table->enum('transaction_type', ['withdraw cash','add cash','transfer']);
            $table->double('amount', 8, 2);
            $table->foreignId('from_user')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('to_user')
                ->constrained('users')
                ->onDelete('cascade');
            $table->enum('status',['pending','completed','failed']);    
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
        Schema::dropIfExists('wallets_transaction');
    }
}
