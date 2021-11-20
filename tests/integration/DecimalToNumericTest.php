<?php

namespace Tests\integration;

use Codeception\Test\Unit;
use Google\Cloud\Spanner\Numeric;
use MgCosta\MysqlParser\Transformer\DataTypes\DecimalType;

class DecimalToNumericTest extends Unit
{
    public function testShouldGetNumericObjectWhenInvokeObject(): void
    {
        $value = "50.50";
        $numeric = (new DecimalType())($value);
        $this->assertInstanceOf(Numeric::class, $numeric);
    }

    public function testShouldGetNullObjectWhenInvokeObjectWithNullValue(): void
    {
        $value = null;
        $numeric = (new DecimalType())($value);
        $this->assertNull($numeric);
    }
}
