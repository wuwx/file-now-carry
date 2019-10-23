<?php

namespace App\Foundation;

use App\Contracts\EncrypterContract;

class Base64Encode implements EncrypterContract
{

    /**
     * Encrypt the given value.
     *
     * @param string $value
     * @return string
     */
    public function encrypt($value)
    {
        return base64_encode($value);
    }

    /**
     * Decrypt the given value.
     *
     * @param string $payload
     * @return string
     */
    public function decrypt($payload)
    {
        return base64_decode($payload);
    }
}