<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast for encrypting/decrypting sensitive data using Laravel's encrypt()/decrypt().
 * Uses AES-256-GCM encryption.
 *
 * Complies with Ley 1581 de 2012 (Colombian data protection law).
 */
class EncryptedCast implements CastsAttributes
{
    /**
     * Cast the given value (decrypt on retrieval).
     *
     * @param  Model  $model
     * @param  mixed  $value
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Exception $e) {
            // If decryption fails, return null (data may not be encrypted yet)
            return null;
        }
    }

    /**
     * Prepare the given value for storage (encrypt on save).
     *
     * @param  Model  $model
     * @param  mixed  $value
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        return encrypt($value);
    }
}
