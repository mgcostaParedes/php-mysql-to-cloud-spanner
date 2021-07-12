<?php

namespace MgCosta\MysqlParser\Contracts;

use MgCosta\MysqlParser\Contracts\ParserBuildable;

interface Processable
{
    public function parseDescribedSchema(ParserBuildable $builder): string;
}
