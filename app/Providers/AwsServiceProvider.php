<?php

namespace App\Providers;

use Aws\Kms\KmsClient;
use Illuminate\Support\ServiceProvider;

final class AwsServiceProvider extends ServiceProvider
{
    public function register()
    {
        // $this->registerS3();

        $this->registerKms();
    }

    // private function registerS3(): void
    // {
    //     $this->app->bind(S3Client::class, function () {
    //         $region = config('aws.region');

    //         return new S3Client(['region' => $region, 'version' => '2006-03-01']);
    //     });
    // }

    private function registerKms(): void
    {
        $this->app->bind(KmsClient::class, fn () => new KmsClient([
            'version' => '2014-11-01',
            'region' => config('aws.region'),
        ]));
    }
}
