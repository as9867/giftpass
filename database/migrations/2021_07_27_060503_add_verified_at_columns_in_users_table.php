<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVerifiedAtColumnsInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN email_verified_at timestamp AFTER email_hash");

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('mobile_verified_at')->nullable()->after('mobile_hash');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN email_verified_at timestamp AFTER mobile_hash");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('mobile_verified_at');
        });
    }
}
