<?php

declare(strict_types=1);

namespace MgCosta\MysqlParser\Transformer;

use MgCosta\MysqlParser\Contracts\DataTypeTransformable;

class DataTypeFactory implements DataTypeTransformable
{
    protected $dataTypeProcessor;

    public function __construct(string $type)
    {
        $objectType = ucfirst(strtolower($type));
        $processorName = "MgCosta\\MysqlParser\\Transformer\\DataTypes\\" . $objectType . "Type";
        if (!class_exists($processorName)) {
            $processorName = "MgCosta\\MysqlParser\\Transformer\\DataTypes\\DefaultType";
        }
        $this->dataTypeProcessor = $processorName;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function transform($value)
    {
        return (new $this->dataTypeProcessor())($value);
    }
}
