<?php

namespace MgCosta\MysqlParser\Contracts;

interface MysqlParsable
{
    public function getDDL(): array;
}
