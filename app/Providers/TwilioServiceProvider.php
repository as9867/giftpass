<?php
namespace App\Providers;

// use Authy\AuthyApi as AuthyApi;
use Illuminate\Support\ServiceProvider;
use Twilio\Rest\Client;

class TwilioServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Client::class, function () {
            $sid = config('twillo.sid');
            $token = config('twilio.token');

            return new Client($sid, $token);
        });
    }
}
