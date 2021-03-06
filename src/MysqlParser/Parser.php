<?php

declare(strict_types=1);

namespace MgCosta\MysqlParser;

use MgCosta\MysqlParser\Contracts\PkOperator;
use MgCosta\MysqlParser\Contracts\TableDescriber;
use MgCosta\MysqlParser\Exceptions\ParserException;
use MgCosta\MysqlParser\Contracts\ParserBuildable;
use MgCosta\MysqlParser\Contracts\MysqlParsable;
use MgCosta\MysqlParser\Contracts\Processable;
use MgCosta\MysqlParser\Processor\CloudSpanner;
use MgCosta\MysqlParser\Traits\TableDescriberTrait;
use RuntimeException;

class Parser implements MysqlParsable, TableDescriber, ParserBuildable, PkOperator
{
    use TableDescriberTrait;

    /**
     * The name of the table to parse
     *
     * @var string
     */
    protected $tableName;

    /**
     * The described table with columns from MySQL
     *
     * @var array
     */
    protected $describedTable = [];

    /**
     * The described table keys from MySQL
     *
     * @var array
     */
    protected $describedKeys = [];

    /**
     * The string with mysql schema to parse
     *
     * @var string
     */
    protected $schema;

    /**
     * The grammatical library to parse the ddl from Mysql
     *
     * @var Processable|CloudSpanner
     */
    protected $processor;

    /**
     * The variable which will be used to define the required primary key if a PK is missing
     *
     * @var string
     */
    protected $defaultID = Dialect::DEFAULT_PRIMARY_KEY;

    /**
     * The bool which will be used to trigger if assign of PK when there's no PK
     *
     * @var bool
     */
    protected $shouldAssignPK = true;

    /**
     * The bool which will be used to trigger if assign a semicolon at end of each statement
     *
     * @var bool
     */
    protected $shouldAssignSemicolon = true;

    public function __construct(Processable $processor = null)
    {
        $this->processor = $processor ?? new CloudSpanner();
    }

    public function shouldAssignSemicolon(bool $state): Parser
    {
        $this->shouldAssignSemicolon = $state;
        return $this;
    }

    public function setTableName(string $tableName): Parser
    {
        $this->tableName = strtolower($tableName);
        return $this;
    }

    public function setDefaultID(string $columnName): Parser
    {
        $this->defaultID = $columnName;
        return $this;
    }

    public function shouldAssignPrimaryKey(bool $state): Parser
    {
        $this->shouldAssignPK = $state;
        return $this;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getDefaultID(): string
    {
        return $this->defaultID;
    }

    public function isPrimaryKeyAssignable(): bool
    {
        return $this->shouldAssignPK;
    }

    public function isSemicolonAssignable(): bool
    {
        return $this->shouldAssignSemicolon;
    }

    public function setDescribedTable(array $table): Parser
    {
        $this->validateAndSetDescribedTable($table, 'describedTable');
        return $this;
    }

    public function getDescribedTable(): array
    {
        return $this->describedTable;
    }

    public function setKeys(array $keys): Parser
    {
        $requiredKeys = [
            'TABLE_NAME', 'COLUMN_NAME', 'CONSTRAINT_NAME',
            'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME'
        ];
        $keys = $this->parseAndValidateArrayKeys($keys, $requiredKeys, 'described keys');

        $this->describedKeys = $keys;
        return $this;
    }

    public function getDescribedKeys(): array
    {
        return $this->describedKeys;
    }

    /**
     * @param bool $withSemicolons
     * @return array
     * @throws Exceptions\PrimaryKeyNotFoundException
     * @throws ParserException
     */
    public function toDDL(bool $withSemicolons = true): array
    {
        if (!empty($this->describedTable)) {
            return $this->processor->parseDescribedSchema($this);
        }

        throw new ParserException("You must define a described table to parse");
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
