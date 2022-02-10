<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domains\Card\Models\Brand;
class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Brand::create(['name' => 'Amazon','logo' => 'img/brand/amazon.png','bg_color' => 'black','terms_and_conditions' => 'Shop for Amazon.in Gift Cards for Birthdays, Weddings & More. Go Cashless! Easy & Fast Delivery. Best Deals. Huge Selection. Low Prices. Top Brands. No Cost EMI Available. Great Offers.','description' =>'Shop for Amazon.in Gift Cards for Birthdays, Weddings & More. Go Cashless! Easy & Fast Delivery. Best Deals. Huge Selection. Low Prices. Top Brands. No Cost EMI Available. Great Offers.','category_id'=> 1]);
        Brand::create(['name' => 'spotify','logo' => 'img/brand/spotify.png','bg_color' => 'black','terms_and_conditions' => 'Shop for Amazon.in Gift Cards for Birthdays, Weddings & More. Go Cashless! Easy & Fast Delivery. Best Deals. Huge Selection. Low Prices. Top Brands. No Cost EMI Available. Great Offers.','description' =>'Shop for Amazon.in Gift Cards for Birthdays, Weddings & More. Go Cashless! Easy & Fast Delivery. Best Deals. Huge Selection. Low Prices. Top Brands. No Cost EMI Available. Great Offers.','category_id'=> 2]);
        Brand::create(['name' => 'Uber','logo' => 'img/brand/uber.png','bg_color' => 'black','terms_and_conditions' => 'Shop for Amazon.in Gift Cards for Birthdays, Weddings & More. Go Cashless! Easy & Fast Delivery. Best Deals. Huge Selection. Low Prices. Top Brands. No Cost EMI Available. Great Offers.','description' =>'Shop for Amazon.in Gift Cards for Birthdays, Weddings & More. Go Cashless! Easy & Fast Delivery. Best Deals. Huge Selection. Low Prices. Top Brands. No Cost EMI Available. Great Offers.','category_id'=> 3]);
        Brand::create(['name' => 'Apple App Store','logo' => 'img/brand/apple_app_store.png','bg_color' => 'black','terms_and_conditions' => 'Shop for Amazon.in Gift Cards for Birthdays, Weddings & More. Go Cashless! Easy & Fast Delivery. Best Deals. Huge Selection. Low Prices. Top Brands. No Cost EMI Available. Great Offers.','description' =>'Shop for Amazon.in Gift Cards for Birthdays, Weddings & More. Go Cashless! Easy & Fast Delivery. Best Deals. Huge Selection. Low Prices. Top Brands. No Cost EMI Available. Great Offers.','category_id'=> 1]);
        Brand::create(['name' => 'IKEA','logo' => 'img/brand/ikea.jpg','bg_color' => 'black','terms_and_conditions' => 'Shop for Amazon.in Gift Cards for Birthdays, Weddings & More. Go Cashless! Easy & Fast Delivery. Best Deals. Huge Selection. Low Prices. Top Brands. No Cost EMI Available. Great Offers.','description' =>'Shop for Amazon.in Gift Cards for Birthdays, Weddings & More. Go Cashless! Easy & Fast Delivery. Best Deals. Huge Selection. Low Prices. Top Brands. No Cost EMI Available. Great Offers.','category_id'=> 2]);
    }
}
