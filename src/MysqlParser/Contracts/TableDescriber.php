<?php

namespace MgCosta\MysqlParser\Contracts;

use MgCosta\MysqlParser\Parser;

interface TableDescriber
{
    public function setDescribedTable(array $table);
    public function getDescribedTable(): array;
}
