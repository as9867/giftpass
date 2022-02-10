<?php

namespace App\Models\Traits;

trait HasToken
{
    /**
     * Generate Personal Access Token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->createToken('Personal Access Token')->accessToken;
    }
}
