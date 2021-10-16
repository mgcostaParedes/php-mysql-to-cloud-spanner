<?php

namespace MgCosta\MysqlParser\Contracts;

interface Processable
{
    public function setAssignableSemicolon(bool $state);
    public function parseDescribedSchema(ParserBuildable $builder): array;
}
