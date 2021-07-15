<?php

namespace MgCosta\MysqlParser\Contracts;

interface DialectGenerator
{
    public function generateTableDetails(string $tableName): string;
    public function generateTableKeysDetails(string $tableName): string;
}
