<?php

namespace MgCosta\MysqlParser\Contracts;

interface SchemaFetchable
{
    public function getTableDetails(string $tableName): string;
    public function getTableKeysDetails(string $tableName): string;
}
