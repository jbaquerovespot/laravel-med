<?php

namespace Tests\Unit\Rules;

use App\Rules\ValidColombianDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValidColombianDocumentTest extends TestCase
{
    #[Test]
    #[DataProvider('validCCProvider')]
    public function it_validates_valid_cc_documents(string $document): void
    {
        $rule = new ValidColombianDocument('CC');

        $passed = true;
        $rule->validate('document_number', $document, function ($message) use (&$passed) {
            $passed = false;
        });

        $this->assertTrue($passed, "CC document {$document} should be valid");
    }

    #[Test]
    #[DataProvider('validTIProvider')]
    public function it_validates_valid_ti_documents(string $document): void
    {
        $rule = new ValidColombianDocument('TI');

        $passed = true;
        $rule->validate('document_number', $document, function ($message) use (&$passed) {
            $passed = false;
        });

        $this->assertTrue($passed, "TI document {$document} should be valid");
    }

    #[Test]
    #[DataProvider('validPAProvider')]
    public function it_validates_valid_pa_documents(string $document): void
    {
        $rule = new ValidColombianDocument('PA');

        $passed = true;
        $rule->validate('document_number', $document, function ($message) use (&$passed) {
            $passed = false;
        });

        $this->assertTrue($passed, "PA document {$document} should be valid");
    }

    #[Test]
    #[DataProvider('validRCProvider')]
    public function it_validates_valid_rc_documents(string $document): void
    {
        $rule = new ValidColombianDocument('RC');

        $passed = true;
        $rule->validate('document_number', $document, function ($message) use (&$passed) {
            $passed = false;
        });

        $this->assertTrue($passed, "RC document {$document} should be valid");
    }

    #[Test]
    #[DataProvider('invalidDocumentProvider')]
    public function it_rejects_invalid_documents(string $document): void
    {
        // Use a rule that requires CC specifically
        $rule = new ValidColombianDocument('CC');

        $passed = true;
        $rule->validate('document_number', $document, function ($message) use (&$passed) {
            $passed = false;
        });

        $this->assertFalse($passed, "Document {$document} should be invalid for CC type");
    }

    #[Test]
    public function it_accepts_any_valid_format_when_type_is_null(): void
    {
        $rule = new ValidColombianDocument;

        // CC
        $passed = true;
        $rule->validate('document_number', '12345678', function ($message) use (&$passed) {
            $passed = false;
        });
        $this->assertTrue($passed);

        // TI
        $passed = true;
        $rule->validate('document_number', '123456-7', function ($message) use (&$passed) {
            $passed = false;
        });
        $this->assertTrue($passed);

        // PA
        $passed = true;
        $rule->validate('document_number', 'AB123456', function ($message) use (&$passed) {
            $passed = false;
        });
        $this->assertTrue($passed);

        // RC
        $passed = true;
        $rule->validate('document_number', 'RC123456', function ($message) use (&$passed) {
            $passed = false;
        });
        $this->assertTrue($passed);
    }

    #[Test]
    public function it_detects_document_type_correctly(): void
    {
        $this->assertEquals('CC', ValidColombianDocument::detectType('12345678'));
        $this->assertEquals('CC', ValidColombianDocument::detectType('1234567890'));
        $this->assertEquals('TI', ValidColombianDocument::detectType('123456-7'));
        $this->assertEquals('PA', ValidColombianDocument::detectType('AB123456'));
        $this->assertEquals('RC', ValidColombianDocument::detectType('RC123456'));
        $this->assertNull(ValidColombianDocument::detectType('invalid-format-xyz'));
    }

    #[Test]
    public function it_validates_cc_with_static_method(): void
    {
        $this->assertTrue(ValidColombianDocument::validateCC('12345678'));
        $this->assertTrue(ValidColombianDocument::validateCC('1234567890'));
        $this->assertFalse(ValidColombianDocument::validateCC('1234567'));
        $this->assertFalse(ValidColombianDocument::validateCC('12345678901'));
    }

    #[Test]
    public function it_validates_ti_with_static_method(): void
    {
        $this->assertTrue(ValidColombianDocument::validateTI('123456-7'));
        $this->assertTrue(ValidColombianDocument::validateTI('12345678-9'));
        $this->assertFalse(ValidColombianDocument::validateTI('1234567'));
        $this->assertFalse(ValidColombianDocument::validateTI('123456-'));
    }

    #[Test]
    public function it_validates_pa_with_static_method(): void
    {
        $this->assertTrue(ValidColombianDocument::validatePA('AB123456'));
        $this->assertTrue(ValidColombianDocument::validatePA('ABCDEF123456'));
        $this->assertFalse(ValidColombianDocument::validatePA('AB12'));
        $this->assertFalse(ValidColombianDocument::validatePA('ABCDEF1234567'));
    }

    public static function validCCProvider(): array
    {
        return [
            '8 digits' => ['12345678'],
            '9 digits' => ['123456789'],
            '10 digits' => ['1234567890'],
        ];
    }

    public static function validTIProvider(): array
    {
        return [
            '6 digits with 1 digit check' => ['123456-7'],
            '7 digits with 1 digit check' => ['1234567-8'],
            '8 digits with 1 digit check' => ['12345678-9'],
            '9 digits with 2 digit check' => ['123456789-12'],
        ];
    }

    public static function validPAProvider(): array
    {
        return [
            '6 alphanumeric' => ['ABC123'],
            'mixed case' => ['AbCdEf'],
            'numbers only' => ['123456'],
            'letters only' => ['ABCDEF'],
            '12 chars max' => ['ABCDEFGHIJ12'],
        ];
    }

    public static function validRCProvider(): array
    {
        return [
            'rc-6-digits' => ['RC123456'],
            'rc-8-digits' => ['RC12345678'],
            'rc-10-digits' => ['RC1234567890'],
            'rc-lowercase' => ['rc123456'],
        ];
    }

    public static function invalidDocumentProvider(): array
    {
        return [
            'empty string' => [''],
            'too short CC (7 digits)' => ['1234567'],
            'too long CC (11 digits)' => ['12345678901'],
            'TI without hyphen' => ['12345678-'],  // trailing hyphen is invalid
            'PA too short' => ['AB12'],
            'PA too long' => ['ABCDEFGHIJ123'],
            'RC without prefix' => ['123456RC'],  // RC in wrong position
            'special chars' => ['123@5678'],
            'letters in CC position' => ['ABC123456XYZ'],  // Too long for PA too
        ];
    }
}
