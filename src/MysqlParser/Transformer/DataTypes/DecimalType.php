<?php

declare(strict_types=1);

namespace MgCosta\MysqlParser\Transformer\DataTypes;

use Google\Cloud\Spanner\Numeric;

class DecimalType
{
    /**
     * @param $value
     * @return Numeric
     */
    public function __invoke($value): Numeric
    {
        return new Numeric($value);
    }
}
