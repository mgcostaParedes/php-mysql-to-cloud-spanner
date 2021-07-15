<?php

namespace MgCosta\MysqlParser\Contracts;

interface GrammarQueryable
{
    public function getTableDetails(string $tableName): string;
    public function getTableKeysDetails(string $tableName): string;
}
