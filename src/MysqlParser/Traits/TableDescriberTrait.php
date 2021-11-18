<?php

declare(strict_types=1);

namespace MgCosta\MysqlParser\Traits;

use RuntimeException;

trait TableDescriberTrait
{
    public function validateAndSetDescribedTable(array $table, string $propertyName): void
    {
        $requiredKeys = ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'];
        $table = $this->parseAndValidateArrayKeys($table, $requiredKeys, 'described table');

        $this->{$propertyName} = $table;
    }

    private function parseAndValidateArrayKeys(array $data, array $keys, string $origin): array
    {
        foreach ($data as $key => $column) {
            if (is_object($column)) {
                $column = (array)$column;
                $data[$key] = $column;
            }
            if (array_diff_key($column, array_flip($keys))) {
                throw new RuntimeException("There's invalid column keys for the " . $origin);
            }
        }
        return $data;
    }
}
