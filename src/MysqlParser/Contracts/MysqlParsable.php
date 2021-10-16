<?php

namespace MgCosta\MysqlParser\Contracts;

interface MysqlParsable
{
    public function toDDL(bool $withSemicolons = true): array;
}
