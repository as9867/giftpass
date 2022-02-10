<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('brand_id')
                ->constrained()
                ->onDelele('cascade');
            $table->string('value', 512)->nullable();
            $table->string('secret', 512)->nullable();
            $table->text('url')->nullable();
            $table->string('srno',512)->nullable();
            $table->dateTime('expiry')->nullable();
            $table->unsignedTinyInteger('active')->default(1);
            $table->string('brand_name',512)->nullable();
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
        Schema::dropIfExists('cards');
    }
}
