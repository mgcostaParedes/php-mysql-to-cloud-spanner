<?php

namespace MgCosta\MysqlParser\Contracts;

interface SpannerTransformable
{
    public function setRows(array $rows);
    public function getRows(): array;
    public function transform(): array;
}
