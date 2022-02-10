<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('bg_color')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->text('description')->nullable();
            $table->text('how_to_redeem')->nullable();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->onDelete('cascade');    
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
        Schema::dropIfExists('brands');
    }
}
