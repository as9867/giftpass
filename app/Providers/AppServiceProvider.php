<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Domains\Marketplace\Models\Marketplace;

/**
 * Class AppServiceProvider.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        View::composer('backend.includes.sidebar', function ($view) {
            $bageCounts = Marketplace::select('listing_type', DB::raw('count(*) as count'))
                ->groupBy('listing_type')
                ->get()
                ->pluck('count', 'listing_type')
                ->toArray();

            $view->with(['badgeCounts' => $bageCounts]);
        });
    }
}
