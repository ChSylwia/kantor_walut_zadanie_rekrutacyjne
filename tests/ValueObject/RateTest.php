<?php

declare(strict_types=1);

namespace App\Tests\ValueObject;

use App\ValueObject\Rate;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RateTest extends TestCase
{
    public function testValidRateCreation(): void
    {
        $rate = new Rate(1.2345);

        $this->assertSame(1.2345, $rate->value);
        $this->assertSame('1.2345', (string) $rate);
    }

    public function testValidRateWithDifferentValues(): void
    {
        $validRates = [0.0001, 0.5, 1.0, 1.5, 100.0, 999.999, 0.123456789];

        foreach ($validRates as $rateValue) {
            $rate = new Rate($rateValue);
            $this->assertSame($rateValue, $rate->value);
        }
    }

    public function testZeroRateThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate must be positive');

        new Rate(0.0);
    }

    public function testNegativeRateThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate must be positive');

        new Rate(-1.0);
    }

    public function testInfiniteRateThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate must be a finite number');

        new Rate(INF);
    }

    public function testNegativeInfiniteRateThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate must be a finite number');

        new Rate(-INF);
    }

    public function testNaNRateThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate must be a finite number');

        new Rate(NAN);
    }

    public function testAddSpreadWithPositiveValue(): void
    {
        $rate = new Rate(1.2345);
        $newRate = $rate->addSpread(0.0055);

        $this->assertSame(1.24, $newRate->value);
    }

    public function testAddSpreadWithNegativeValue(): void
    {
        $rate = new Rate(1.5);
        $newRate = $rate->addSpread(-0.1);

        $this->assertSame(1.4, $newRate->value);
    }

    public function testAddSpreadWithZeroValue(): void
    {
        $rate = new Rate(1.2345);
        $newRate = $rate->addSpread(0.0);

        $this->assertSame(1.2345, $newRate->value);
    }

    public function testAddSpreadWithInfiniteValueThrowsException(): void
    {
        $rate = new Rate(1.0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Spread must be a finite number');

        $rate->addSpread(INF);
    }

    public function testAddSpreadWithNaNValueThrowsException(): void
    {
        $rate = new Rate(1.0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Spread must be a finite number');

        $rate->addSpread(NAN);
    }

    public function testMultiplyByValidValue(): void
    {
        $rate = new Rate(1.5);
        $newRate = $rate->multiplyBy(2.0);

        $this->assertSame(3.0, $newRate->value);
    }

    public function testMultiplyByDecimalValue(): void
    {
        $rate = new Rate(10.0);
        $newRate = $rate->multiplyBy(0.1);

        $this->assertSame(1.0, $newRate->value);
    }

    public function testMultiplyByZeroThrowsException(): void
    {
        $rate = new Rate(1.0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Multiplier must be positive');

        $rate->multiplyBy(0.0);
    }

    public function testMultiplyByNegativeValueThrowsException(): void
    {
        $rate = new Rate(1.0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Multiplier must be positive');

        $rate->multiplyBy(-1.0);
    }

    public function testMultiplyByInfiniteValueThrowsException(): void
    {
        $rate = new Rate(1.0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Multiplier must be a finite number');

        $rate->multiplyBy(INF);
    }

    public function testMultiplyByNaNValueThrowsException(): void
    {
        $rate = new Rate(1.0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Multiplier must be a finite number');

        $rate->multiplyBy(NAN);
    }

    public function testRateEqualityWithSameValues(): void
    {
        $rate1 = new Rate(1.2345);
        $rate2 = new Rate(1.2345);

        $this->assertTrue($rate1->equals($rate2));
        $this->assertTrue($rate2->equals($rate1));
    }

    public function testRateEqualityWithDifferentValues(): void
    {
        $rate1 = new Rate(1.2345);
        $rate2 = new Rate(1.2346);

        $this->assertFalse($rate1->equals($rate2));
        $this->assertFalse($rate2->equals($rate1));
    }

    public function testRateEqualityWithFloatingPointPrecision(): void
    {
        // Test arithmetic precision issues
        $rate1 = new Rate(0.1 + 0.2); // This might be 0.30000000000000004
        $rate2 = new Rate(0.3);

        // With BCMath precision, these should be considered equal
        $this->assertTrue($rate1->equals($rate2));
    }

    public function testIsGreaterThan(): void
    {
        $rate1 = new Rate(1.5);
        $rate2 = new Rate(1.0);

        $this->assertTrue($rate1->isGreaterThan($rate2));
        $this->assertFalse($rate2->isGreaterThan($rate1));
    }

    public function testIsLessThan(): void
    {
        $rate1 = new Rate(1.0);
        $rate2 = new Rate(1.5);

        $this->assertTrue($rate1->isLessThan($rate2));
        $this->assertFalse($rate2->isLessThan($rate1));
    }

    public function testComparisonWithEqualValues(): void
    {
        $rate1 = new Rate(1.2345);
        $rate2 = new Rate(1.2345);

        $this->assertFalse($rate1->isGreaterThan($rate2));
        $this->assertFalse($rate1->isLessThan($rate2));
        $this->assertTrue($rate1->equals($rate2));
    }

    public function testToStringConversion(): void
    {
        $rate = new Rate(1.2345);

        $this->assertSame('1.2345', (string) $rate);
        $this->assertSame((string) $rate->value, (string) $rate);
    }

    public function testArithmeticPrecisionWithAddSpread(): void
    {
        // Test floating point precision issues
        $rate = new Rate(0.1);
        $newRate = $rate->addSpread(0.2);

        // Should be exactly 0.3, not 0.30000000000000004
        $expectedRate = new Rate(0.3);
        $this->assertTrue($newRate->equals($expectedRate));
    }

    public function testArithmeticPrecisionWithMultiply(): void
    {
        // Test floating point precision issues
        $rate = new Rate(0.1);
        $newRate = $rate->multiplyBy(3.0);

        // Should be exactly 0.3, not close approximation
        $expectedRate = new Rate(0.3);
        $this->assertTrue($newRate->equals($expectedRate));
    }

    /**
     * @dataProvider invalidRateProvider
     */
    public function testInvalidRatesThrowExceptions(float $invalidRate, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new Rate($invalidRate);
    }

    public function invalidRateProvider(): array
    {
        return [
            [0.0, 'Rate must be positive'],
            [-0.1, 'Rate must be positive'],
            [-1.0, 'Rate must be positive'],
            [-999.999, 'Rate must be positive'],
            [INF, 'Rate must be a finite number'],
            [-INF, 'Rate must be a finite number'],
            [NAN, 'Rate must be a finite number'],
        ];
    }

    /**
     * @dataProvider validRateProvider
     */
    public function testValidRatesCreation(float $validRate): void
    {
        $rate = new Rate($validRate);
        $this->assertSame($validRate, $rate->value);
    }

    public function validRateProvider(): array
    {
        return [
            [0.0001],
            [0.1],
            [0.5],
            [1.0],
            [1.5],
            [2.0],
            [10.5],
            [100.0],
            [999.999],
            [0.123456789],
            [PHP_FLOAT_MIN],
            [PHP_FLOAT_MAX],
        ];
    }
}
