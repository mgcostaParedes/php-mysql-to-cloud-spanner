<?php

namespace MgCosta\MysqlParser\Contracts;

interface Processable
{
    public function parseDescribedSchema(ParserBuildable $builder): array;
}
