<?php

namespace MgCosta\MysqlParser\Contracts;

interface Flushable
{
    public function flush(): void;
}