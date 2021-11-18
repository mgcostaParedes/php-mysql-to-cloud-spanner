<?php

declare(strict_types=1);

namespace MgCosta\MysqlParser\Transformer\DataTypes;

class DefaultType
{
    /**
     * @param $value
     * @return mixed
     */
    public function __invoke($value)
    {
        return $value;
    }
}
