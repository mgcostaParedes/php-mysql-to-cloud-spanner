<?php

declare(strict_types=1);

namespace MgCosta\MysqlParser\Traits;

trait TableColumnTrait
{
    public function cleanTypeName(string $name): string
    {
        // remove extra details from the type
        $name = trim(preg_replace('/\s*\([^)]*\)/', '', $name));
        // remove white spaces from the type
        return preg_replace('/\s+/', '', $name);
    }
}
