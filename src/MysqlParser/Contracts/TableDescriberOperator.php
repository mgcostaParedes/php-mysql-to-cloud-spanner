<?php

namespace MgCosta\MysqlParser\Contracts;

use MgCosta\MysqlParser\Parser;

interface TableDescriberOperator
{
    public function setDescribedTable(array $table);
    public function getDescribedTable(): array;
}
