<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()
                ->constrained()
                ->onDelete('cascade');
            $table->enum('method_of_delivery', ['email','sms'])->nullable();
            $table->string('recipient')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('date_of_delivery');
            $table->foreignId('card_id')->nullable()
                ->constrained('cards')
                ->onDelete('cascade');
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
        Schema::dropIfExists('gifts');
    }
}
