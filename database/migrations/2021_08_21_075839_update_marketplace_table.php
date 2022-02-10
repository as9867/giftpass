<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMarketplaceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketplace', function (Blueprint $table) {
            $table->boolean('cancel_request')->default(0);
            $table->foreignId('buyer_id')->nullable()
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
        Schema::table('marketplace', function (Blueprint $table) {
            $table->dropColumn('cancel_request');

        });
    }
}
