<?php

declare(strict_types=1);

namespace MgCosta\MysqlParser\Transformer\DataTypes;

class DecimalType
{
    /**
     * @param $value
     * @return float
     */
    public function __invoke($value): float
    {
        return floatval($value);
    }
}
