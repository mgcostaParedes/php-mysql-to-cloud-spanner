<?php

namespace MgCosta\MysqlParser\Contracts;

use MgCosta\MysqlParser\Parser;

interface PkOperator
{
    public function setDefaultID(string $columnName): Parser;
    public function getDefaultID(): string;
    public function setShouldAssignPK(bool $state): Parser;
    public function shouldAssignPK(): bool;
}
