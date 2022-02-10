<?php

namespace App\Models\Traits;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Support\Facades\Crypt;
use Schema;

trait EncryptableDbAttributes
{
    public function getAttributeValue($key)
    {
        $value = $this->getAttributeFromArray($key);

        if ($this->hasGetMutator($key)) {
            return $this->mutateAttribute($key, $value);
        }

        if (in_array($key, $this->encryptable) && ! is_null($value) && $value !== '') {
            $value = $this->decrypt($value);
        }

        if ($this->hasCast($key)) {
            return $this->castAttribute($key, $value);
        }

        if (in_array($key, $this->getDates()) &&
            ! is_null($value)) {
            return $this->asDateTime($value);
        }

        return $value;
    }

    public function setAttribute($key, $value)
    {
        if (is_null($value) || ! in_array($key, $this->encryptable)) {
            return parent::setAttribute($key, $value);
        }

        if ($this->isJsonCastable($key) && ! is_null($value)) {
            $value = $this->castAttributeAsJson($key, $value);
        }

        if (in_array($key . '_hash', Schema::getColumnListing($this->getTable()))) {
            parent::setAttribute($key . '_hash', hash_sha($value));
        }

        $value = $this->encrypt($value);

        return parent::setAttribute($key, $value);
    }

    public function attributesToArray(): array
    {
        $attributes = $this->addDateAttributesToArray(
            $attributes = $this->getArrayableAttributes()
        );

        $attributes = $this->addMutatedAttributesToArray(
            $attributes,
            $mutatedAttributes = $this->getMutatedAttributes()
        );

        $attributes = $this->decryptAttributes($attributes);

        $attributes = $this->addCastAttributesToArray(
            $attributes,
            $mutatedAttributes
        );

        foreach ($this->getArrayableAppends() as $key) {
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }

        return $attributes;
    }

    private function decryptAttributes(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            if (! in_array($key, $this->encryptable) || is_null($value) || $value === '') {
                continue;
            }

            $attributes[$key] = $this->decrypt($value);
        }

        return $attributes;
    }

    private function encrypt($value)
    {
        try {
            $value = Crypt::encrypt($value);
        } catch (EncryptException $e) {
        }

        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function decrypt($value)
    {
        try {
            $value = Crypt::decrypt($value);
        } catch (DecryptException $e) {
        }

        return $value;
    }
}
