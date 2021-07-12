<?php

namespace MgCosta\MysqlParser\Contracts;

interface DescribeExtractor
{
    public function getTableDetails(string $tableName): string;
    public function getTableKeysDetails(string $tableName): string;
}
