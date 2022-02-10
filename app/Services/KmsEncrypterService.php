<?php

namespace App\Services;

use Aws\Exception\AwsException;
use Aws\Kms\KmsClient;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Contracts\Encryption\StringEncrypter;

final class KmsEncrypterService implements Encrypter, StringEncrypter
{
    public function __construct(private KmsClient $client, private string $key, private array $context)
    {
    }

    public function encrypt($value, $serialize = true)
    {
        try {
            return base64_encode($this->client->encrypt([
                'KeyId' => $this->key,
                'Plaintext' => $value,
                'EncryptionContext' => $this->context,
            ])->get('CiphertextBlob'));
        } catch (AwsException $e) {
            throw new EncryptException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function decrypt($payload, $unserialize = true)
    {
        try {
            $result = $this->client->decrypt([
                'CiphertextBlob' => base64_decode($payload),
                'EncryptionContext' => $this->context,
            ]);

            return $result['Plaintext'];
        } catch (AwsException $e) {
            throw new DecryptException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function encryptString($value): string
    {
        return $this->encrypt($value, false);
    }

    public function decryptString($payload): string
    {
        return $this->decrypt($payload, false);
    }
}
