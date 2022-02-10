<?php

namespace App\Providers;

use App\Services\KmsEncrypterService;
use Aws\Kms\KmsClient;
use Illuminate\Support\ServiceProvider;

final class EncryptionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(KmsEncrypterService::class, function () {
            $key = config('aws.keyId');

            $context = config('aws.context');

            $client = $this->app->make(KmsClient::class);

            return new KmsEncrypterService($client, $key, $context ?? []);
        });

        $this->app->alias(KmsEncrypterService::class, 'encrypter');

        $this->app->alias(KmsEncrypterService::class, \Illuminate\Contracts\Encryption\Encrypter::class);

        $this->app->alias(KmsEncrypterService::class, \Illuminate\Contracts\Encryption\StringEncrypter::class);
    }
}
