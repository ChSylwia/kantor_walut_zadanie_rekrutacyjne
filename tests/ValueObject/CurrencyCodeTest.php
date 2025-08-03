<?php

declare(strict_types=1);

namespace App\Tests\ValueObject;

use App\ValueObject\CurrencyCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CurrencyCodeTest extends TestCase
{
    public function testValidCurrencyCodeCreation(): void
    {
        $currencyCode = new CurrencyCode('USD');

        $this->assertSame('USD', $currencyCode->value);
        $this->assertSame('USD', (string) $currencyCode);
    }

    public function testValidCurrencyCodesWithDifferentValues(): void
    {
        $validCodes = ['EUR', 'GBP', 'JPY', 'CHF', 'PLN', 'CAD', 'AUD'];

        foreach ($validCodes as $code) {
            $currencyCode = new CurrencyCode($code);
            $this->assertSame($code, $currencyCode->value);
        }
    }

    public function testEmptyCurrencyCodeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code cannot be empty');

        new CurrencyCode('');
    }

    public function testTooShortCurrencyCodeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code must be exactly 3 characters long');

        new CurrencyCode('US');
    }

    public function testTooLongCurrencyCodeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code must be exactly 3 characters long');

        new CurrencyCode('USDT');
    }

    public function testNonAlphabeticCurrencyCodeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code must contain only letters');

        new CurrencyCode('US1');
    }

    public function testCurrencyCodeWithNumbersThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code must contain only letters');

        new CurrencyCode('123');
    }

    public function testCurrencyCodeWithSpecialCharactersThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code must contain only letters');

        new CurrencyCode('US$');
    }

    public function testLowercaseCurrencyCodeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code must be uppercase');

        new CurrencyCode('usd');
    }

    public function testMixedCaseCurrencyCodeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code must be uppercase');

        new CurrencyCode('Usd');
    }

    public function testCurrencyCodeEqualityWithSameValues(): void
    {
        $currencyCode1 = new CurrencyCode('USD');
        $currencyCode2 = new CurrencyCode('USD');

        $this->assertTrue($currencyCode1->equals($currencyCode2));
        $this->assertTrue($currencyCode2->equals($currencyCode1));
    }

    public function testCurrencyCodeEqualityWithDifferentValues(): void
    {
        $currencyCode1 = new CurrencyCode('USD');
        $currencyCode2 = new CurrencyCode('EUR');

        $this->assertFalse($currencyCode1->equals($currencyCode2));
        $this->assertFalse($currencyCode2->equals($currencyCode1));
    }

    public function testToStringConversion(): void
    {
        $currencyCode = new CurrencyCode('GBP');

        $this->assertSame('GBP', (string) $currencyCode);
        $this->assertSame($currencyCode->value, (string) $currencyCode);
    }

    /**
     * @dataProvider invalidCurrencyCodeProvider
     */
    public function testInvalidCurrencyCodesThrowExceptions(string $invalidCode, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new CurrencyCode($invalidCode);
    }

    public function invalidCurrencyCodeProvider(): array
    {
        return [
            ['', 'Currency code cannot be empty'],
            ['A', 'Currency code must be exactly 3 characters long'],
            ['AB', 'Currency code must be exactly 3 characters long'],
            ['ABCD', 'Currency code must be exactly 3 characters long'],
            ['AB1', 'Currency code must contain only letters'],
            ['A1B', 'Currency code must contain only letters'],
            ['1AB', 'Currency code must contain only letters'],
            ['123', 'Currency code must contain only letters'],
            ['AB@', 'Currency code must contain only letters'],
            ['A@B', 'Currency code must contain only letters'],
            ['@AB', 'Currency code must contain only letters'],
            ['abc', 'Currency code must be uppercase'],
            ['Abc', 'Currency code must be uppercase'],
            ['aBc', 'Currency code must be uppercase'],
            ['abC', 'Currency code must be uppercase'],
        ];
    }
}
