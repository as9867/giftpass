<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_activity', function (Blueprint $table) {
            $table->foreignId('offered_brand_id')->nullable()
                ->constrained('brands')
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
        Schema::table('user_activity', function (Blueprint $table) {
            $table->dropColumn('offered_brand_id');
        });
    }
}
