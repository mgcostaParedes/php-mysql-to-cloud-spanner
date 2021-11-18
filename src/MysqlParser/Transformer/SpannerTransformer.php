<?php

declare(strict_types=1);

namespace MgCosta\MysqlParser\Transformer;

use MgCosta\MysqlParser\Contracts\SpannerTransformable;
use MgCosta\MysqlParser\Contracts\TableDescriberOperator;
use MgCosta\MysqlParser\Traits\TableColumnTrait;
use MgCosta\MysqlParser\Traits\TableDescriberTrait;

class SpannerTransformer implements TableDescriberOperator, SpannerTransformable
{
    use TableDescriberTrait, TableColumnTrait;
    /**
     * The described table with columns from MySQL
     *
     * @var array
     */
    protected $describedTable = [];

    /**
     * The rows which will be transformed
     *
     * @var array
     */
    protected $rows;

    public function setDescribedTable(array $table): SpannerTransformer
    {
        $this->validateAndSetDescribedTable($table, 'describedTable');
        return $this;
    }

    public function getDescribedTable(): array
    {
        return $this->describedTable;
    }

    public function setRows(array $rows): SpannerTransformer
    {
        $this->rows = $rows;
        return $this;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function transform(): array
    {
        $factories = $this->getMappedFactories();
        return array_map(function ($row) use ($factories) {
            foreach ($row as $key => $value) {
                if (isset($factories[$key])) {
                    if (is_object($row)) {
                        $row->{$key} = $factories[$key]->transform($value);
                        continue;
                    }
                    $row[$key] = $factories[$key]->transform($value);
                }
            }
            return $row;
        }, $this->rows);
    }

    private function getMappedFactories(): array
    {
        if (empty($this->rows)) {
            return [];
        }

        $propertiesToTransform = [];
        $firstValue = $this->rows[0];

        foreach ($firstValue as $key => $value) {
            $columnIndex = array_search($key, array_column($this->describedTable, 'Field'));
            if ($columnIndex !== false) {
                $propertiesToTransform[$key] = new DataTypeFactory(
                    $this->cleanTypeName($this->describedTable[$columnIndex]['Type'])
                );
            }
        }
        return $propertiesToTransform;
    }
}
