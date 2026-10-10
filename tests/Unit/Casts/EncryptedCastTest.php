<?php

namespace Tests\Unit\Casts;

use App\Casts\EncryptedCast;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EncryptedCastTest extends TestCase
{
    private EncryptedCast $cast;

    private Model $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cast = new EncryptedCast;
        $this->model = $this->createMock(Model::class);
    }

    #[Test]
    public function it_encrypts_value_on_set(): void
    {
        $plainText = 'sensitive-data@example.com';
        $encrypted = $this->cast->set($this->model, 'email', $plainText, []);

        $this->assertNotEquals($plainText, $encrypted);
        $this->assertNotEquals('sensitive-data@example.com', $encrypted);
        $this->assertIsString($encrypted);

        // Verify it can be decrypted
        $decrypted = decrypt($encrypted);
        $this->assertEquals($plainText, $decrypted);
    }

    #[Test]
    public function it_decrypts_value_on_get(): void
    {
        $plainText = 'sensitive-phone-number';

        // First encrypt the value
        $encrypted = encrypt($plainText);

        // Then test the get method
        $decrypted = $this->cast->get($this->model, 'phone', $encrypted, []);

        $this->assertEquals($plainText, $decrypted);
    }

    #[Test]
    public function it_handles_null_value_on_set(): void
    {
        $result = $this->cast->set($this->model, 'email', null, []);

        $this->assertNull($result);
    }

    #[Test]
    public function it_handles_null_value_on_get(): void
    {
        $result = $this->cast->get($this->model, 'email', null, []);

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_on_decryption_failure(): void
    {
        // Pass invalid encrypted data
        $result = $this->cast->get($this->model, 'email', 'invalid-encrypted-data', []);

        $this->assertNull($result);
    }

    #[Test]
    public function it_performs_round_trip_encryption(): void
    {
        $originalValue = 'test@example.com';

        // Encrypt
        $encrypted = $this->cast->set($this->model, 'email', $originalValue, []);

        // Decrypt
        $decrypted = $this->cast->get($this->model, 'email', $encrypted, []);

        $this->assertEquals($originalValue, $decrypted);
    }

    #[Test]
    public function it_handles_special_characters(): void
    {
        $specialChars = 'áéíóúñ@#$%^&*(){}[]|\\:;"\'<>?,./~`';

        $encrypted = $this->cast->set($this->model, 'data', $specialChars, []);
        $decrypted = $this->cast->get($this->model, 'data', $encrypted, []);

        $this->assertEquals($specialChars, $decrypted);
    }

    #[Test]
    public function it_handles_unicode_characters(): void
    {
        $unicode = '用户@example.com • 🎉 🇨🇴';

        $encrypted = $this->cast->set($this->model, 'data', $unicode, []);
        $decrypted = $this->cast->get($this->model, 'data', $encrypted, []);

        $this->assertEquals($unicode, $decrypted);
    }

    #[Test]
    public function it_handles_empty_string(): void
    {
        $empty = '';

        $encrypted = $this->cast->set($this->model, 'data', $empty, []);
        $decrypted = $this->cast->get($this->model, 'data', $encrypted, []);

        $this->assertEquals($empty, $decrypted);
    }

    #[Test]
    public function it_produces_different_ciphertext_for_same_plaintext(): void
    {
        $plainText = 'same-value';

        $encrypted1 = $this->cast->set($this->model, 'email', $plainText, []);
        $encrypted2 = $this->cast->set($this->model, 'email', $plainText, []);

        // Due to random IV, same plaintext should produce different ciphertext
        $this->assertNotEquals($encrypted1, $encrypted2);

        // But both should decrypt to the same value
        $this->assertEquals($plainText, decrypt($encrypted1));
        $this->assertEquals($plainText, decrypt($encrypted2));
    }

    #[Test]
    public function it_handles_long_strings(): void
    {
        $longString = str_repeat('a', 10000);

        $encrypted = $this->cast->set($this->model, 'data', $longString, []);
        $decrypted = $this->cast->get($this->model, 'data', $encrypted, []);

        $this->assertEquals($longString, $decrypted);
    }
}
