<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domains\Card\Models\Categories;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Categories::create(['name' => 'Music']);
        Categories::create(['name' => 'Sport']);
        Categories::create(['name' => 'Movies']);
        Categories::create(['name' => 'Fashion']);
    }
}
