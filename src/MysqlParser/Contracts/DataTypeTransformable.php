<?php

namespace MgCosta\MysqlParser\Contracts;

interface DataTypeTransformable
{
    public function transform($value);
}
