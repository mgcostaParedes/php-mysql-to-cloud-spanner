<?php

declare(strict_types=1);

namespace MgCosta\MysqlParser\Transformer\DataTypes;

use Google\Cloud\Spanner\Numeric;

class DecimalType
{
    /**
     * @param $value
     * @return Numeric|null
     */
    public function __invoke($value)
    {
        return !empty($value) ? new Numeric($value) : null;
    }
}
