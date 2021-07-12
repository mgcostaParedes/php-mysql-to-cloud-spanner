<?php

namespace MgCosta\MysqlParser\Contracts;

interface MysqlParsable
{
    public function parse(): string;
}
