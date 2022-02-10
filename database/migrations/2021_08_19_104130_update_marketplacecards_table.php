<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMarketplacecardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketplace_cards', function (Blueprint $table) {
            $table->foreignId('trading_brand_id')->nullable()->after('brand_id')
                  ->constrained('brands')
                  ->onDelete('cascade');
            $table->boolean('recive_other_brands')->after('trading_amount')->default(0);      
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('marketplace_cards', function (Blueprint $table) {
            $table->dropColumn('trading_brand_id');
            $table->dropColumn('recive_other_brands');
        });
    }
}
