<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMarketplacetable1Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketplace', function (Blueprint $table) {
            $table->text('admin_reason')->nullable();
        });

        Schema::table('biddings', function (Blueprint $table) {
            $table->text('admin_reason')->nullable();
        });

        Schema::table('offer_trades', function (Blueprint $table) {
            $table->text('admin_reason')->nullable();
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
            $table->dropColumn('admin_reason');
        });

        Schema::table('biddings', function (Blueprint $table) {
            $table->dropColumn('admin_reason');
        });

        Schema::table('offer_trades', function (Blueprint $table) {
            $table->dropColumn('admin_reason');
        });
    }
}
