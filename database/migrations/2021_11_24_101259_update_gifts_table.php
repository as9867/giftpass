<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateGiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('gifts', function (Blueprint $table) {
            $table->foreignId('sender_user_id')->nullable()
                ->constrained('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gifts', function (Blueprint $table) {
            $table->dropColumn('sender_user_id');
        });
    }
}
