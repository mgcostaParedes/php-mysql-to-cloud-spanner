<?php

declare(strict_types=1);

namespace MgCosta\MysqlParser\Processor;

use InvalidArgumentException;
use MgCosta\MysqlParser\Contracts\Flushable;
use MgCosta\MysqlParser\Contracts\ParserBuildable;
use MgCosta\MysqlParser\Contracts\Processable;
use MgCosta\MysqlParser\Dialect;
use MgCosta\MysqlParser\Parser;

class CloudSpanner implements Processable, Flushable
{
    /**
     * The available mysql keys parsed by describe method
     *
     * @var string[]
     */
    protected $availableMysqlKeys = ['PRI', 'MUL', 'UNI'];

    /**
     * The array which will store the primary keys to the spanner ddl
     *
     * @var array
     */
    protected $primaryKeys = [];

    /**
     * The array which will store the foreign keys to the spanner ddl
     *
     * @var array
     */
    protected $foreignKeys = [];

    /**
     * The array which will store the unique indexes to the spanner ddl
     *
     * @var array
     */
    protected $uniqueIndexes = [];

    /**
     * The array which will store the secondary indexes to the spanner ddl
     *
     * @var array
     */
    protected $secondaryIndexes = [];

    /**
     * The maximum length for strings to cloud spanner
     *
     * @var int
     */
    private $maxStringLength = 2621440;

    /**
     * The data types from mysql which spanner does not support currently
     *
     * @var string[]
     */
    private $unavailableDataTypes = [
        'geometry', 'geometrycollection', 'point', 'linestring', 'polygon',
        'multipoint', 'multipolygon'
    ];

    /**
     * The data types from mysql which should be parsed to int64 type
     *
     * @var string[]
     */
    private $typesAsInt64 = [
        'bigintegerunsigned',
        'mediumintegerunsigned',
        'smallintegerunsigned',
        'bigint',
        'mediumint',
        'smallint',
        'int',
        'intunsigned',
        'bigintunsigned',
        'mediumintunsigned',
        'smallintunsigned'
    ];

    /**
     * The described columns from MySQL
     *
     * @var string[]
     */
    private $columns;

    /**
     * The name of table to create
     *
     * @var string
     */
    private $tableName;

    /**
     * Method to parse the describable table from PHP PDO Mysql to raw cloud spanner ddl
     *
     * @param Parser $builder
     * @return array
     */
    public function parseDescribedSchema(ParserBuildable $builder): array
    {
        $this->tableName = $builder->getTableName();
        $this->columns = $builder->getDescribedTable();
        $this->assignKeys($builder);

        $tableDDL = 'CREATE TABLE ' . $this->tableName . ' (' . PHP_EOL;

        foreach ($this->columns as $index => $column) {
            // find extra details from the column type inside parenthesis
            preg_match('/\\((.*?)\\)/', $column['Type'], $columnTypeDetails);
            // remove extra details from the type
            $type = trim(preg_replace('/\s*\([^)]*\)/', '', $column['Type']));
            // remove white spaces from the type
            $type = preg_replace('/\s+/', '', $type);

            $method = 'compile' . ucfirst($type);
            // compile column schema to cloud spanner ddl
            $column['Details'] = $columnTypeDetails;
            $column['isLastOne'] = $index === array_key_last($this->columns);

            if (in_array($type, $this->unavailableDataTypes)) {
                $method = 'compileUnavailableTypes';
            }

            if (in_array($type, $this->typesAsInt64)) {
                $method = 'compileInteger';
            }

            $tableDDL .= $this->$method($column);
        }

        $tableDDL .= !empty($this->foreignKeys) ? $this->compileForeignKeys() : PHP_EOL . ') ';
        $tableDDL .= 'PRIMARY KEY (' . implode(",", $this->primaryKeys) . ");";

        $output = [ $tableDDL ];

        if (!empty($this->uniqueIndexes)) {
            $output = array_merge($output, $this->compileUniqueIndexes());
        }

        if (!empty($this->secondaryIndexes)) {
            $output = array_merge($output, $this->compileSecondaryIndexes());
        }

        // reset the table details on object before returns the ddl
        $this->flush();

        return $output;
    }

    public function flush(): void
    {
        $this->tableName = null;
        $this->columns = null;
        $this->primaryKeys = [];
        $this->secondaryIndexes = [];
        $this->foreignKeys = [];
        $this->uniqueIndexes = [];
    }

    private function compileChar(array $column): string
    {
        return $this->compileVarchar($column);
    }

    private function compileVarchar(array $column): string
    {
        $stringCol = $column['Field'] . ' STRING(';
        // if it has no details assign default size as 255
        $stringCol .= !empty($column['Details']) ? $column['Details'][1] : '255';

        return $stringCol . ')' . $this->resolveAppends($column);
    }

    private function compileDecimal(array $column): string
    {
        return $column['Field'] . ' NUMERIC' . $this->resolveAppends($column);
    }

    private function compileDouble(array $column): string
    {
        return $this->compileFloat($column);
    }

    private function compileFloat(array $column): string
    {
        return $column['Field'] . ' FLOAT64' . $this->resolveAppends($column);
    }

    private function compileTinyint(array $column): string
    {
        return $this->compileBoolean($column);
    }

    private function compileBool(array $column): string
    {
        return $this->compileBoolean($column);
    }

    private function compileBoolean(array $column): string
    {
        return $column['Field'] . ' BOOL' . $this->resolveAppends($column);
    }

    private function compileInteger(array $column): string
    {
        return $column['Field'] . ' INT64' . $this->resolveAppends($column);
    }

    private function compileDate(array $column): string
    {
        return $column['Field'] . ' DATE' . $this->resolveAppends($column);
    }

    private function compileDatetime(array $column): string
    {
        return $this->compileTimestamp($column);
    }

    private function compileTimestamp(array $column): string
    {
        $options = null;
        if ($column['Default'] === 'CURRENT_TIMESTAMP') {
            $options = ' OPTIONS (allow_commit_timestamp=true)';
        }
        return $column['Field'] . ' TIMESTAMP' . $this->resolveAppends($column, $options);
    }

    private function compileTime(array $column): string
    {
        $column['Details'][1] = '50'; // assign default size to time
        return $this->compileVarchar($column);
    }

    private function compileEnum(array $column): string
    {
        $column['Details'][1] = '255'; // assign default size
        return $this->compileVarchar($column);
    }

    private function compileYear(array $column): string
    {
        $column['Details'][1] = '4'; // assign size to year
        return $this->compileVarchar($column);
    }

    private function compileSet(array $column): string
    {
        return $column['Field'] . ' ARRAY<STRING(255)>' . $this->resolveAppends($column);
    }

    private function compileTinytext(array $column): string
    {
        return $this->compileVarchar($column);
    }

    private function compileText(array $column): string
    {
        return $column['Field'] . ' STRING(65535)' . $this->resolveAppends($column);
    }

    private function compileMediumtext(array $column): string
    {
        return $this->compileLongtext($column);
    }

    private function compileLongtext(array $column): string
    {
        return $column['Field'] . ' STRING(' . $this->maxStringLength . ')' .
            $this->resolveAppends($column);
    }

    private function compileUnavailableTypes(array $column): string
    {
        return $column['Field'] . ' STRING(1000)' . $this->resolveAppends($column);
    }

    private function compileBlob(array $column): string
    {
        return $column['Field'] . ' BYTES(10485760)' . $this->resolveAppends($column);
    }

    private function compileJson(array $column): string
    {
        return $this->compileLongtext($column);
    }

    /**
     * Method to resolve all the possible appends for the column string
     *
     * @param array $column
     * @param string|null $options
     * @return string
     */
    private function resolveAppends(array $column, string $options = null): string
    {
        $str = '';

        if ($column['Null'] === 'NO') {
            $str .= ' NOT NULL';
        }

        if ($options) {
            $str .= $options;
        }

        if ($column['isLastOne']) {
            return $str;
        }

        return $str . ',' . PHP_EOL;
    }

    protected function assignKeys(ParserBuildable $builder): void
    {
        foreach ($this->columns as $column) {
            if (!empty($column['Key'])) {
                $this->assignColumnKey($builder, $column['Field'], $column['Key']);
            }
        }

        // if no primary key founded on table, we should assign a default id column
        if (empty($this->primaryKeys)) {
            $this->primaryKeys[] = Dialect::DEFAULT_PRIMARY_KEY;
            array_unshift($this->columns, Dialect::DEFAULT_PRIMARY_KEY_PROPS);
        }
    }

    protected function assignColumnKey(ParserBuildable $builder, string $field, string $type): void
    {
        if (!in_array($type, $this->availableMysqlKeys, true)) {
            throw new InvalidArgumentException('Column key type must be "PRI", "MUL" or "UNI"');
        }

        $keys = $builder->getDescribedKeys();
        $fieldArrayIndex = array_search($field, array_column($keys, 'COLUMN_NAME'));

        if ($fieldArrayIndex === false && $type !== 'PRI' && $type !== 'MUL') {
            throw new InvalidArgumentException('Details for the key ' . $field . ' not found, provide it on "setKeys"');
        }

        if ($type === 'PRI') {
            $this->primaryKeys[] = $field;
            return;
        }

        $keyDetails = [];
        if ($fieldArrayIndex !== false) {
            $keyDetails = $keys[$fieldArrayIndex];
        }

        if ($type === 'MUL' && !empty($keyDetails)) {
            $this->foreignKeys[] = $keyDetails;
        }

        if ($type === 'MUL' && empty($keyDetails)) {
            $this->secondaryIndexes[] = $field;
        }

        if ($type === 'UNI') {
            $this->uniqueIndexes[] = $keyDetails;
        }
    }

    private function compileForeignKeys(): string
    {
        $constraints = ',' . PHP_EOL;
        foreach ($this->foreignKeys as $key => $foreign) {
            $commaAppends = ($key !== count($this->foreignKeys) - 1) ? ',' . PHP_EOL : '';
            $constraints .= 'CONSTRAINT ' . $foreign['CONSTRAINT_NAME'] . ' FOREIGN KEY (' . $foreign['COLUMN_NAME'] .
                ')' . ' REFERENCES ' . $foreign['REFERENCED_TABLE_NAME'] .  ' (' . $foreign['REFERENCED_COLUMN_NAME'] .
                ')' . $commaAppends;
        }
        return $constraints . PHP_EOL . ') ';
    }

    private function compileUniqueIndexes(): array
    {
        $indexes = [];
        foreach ($this->uniqueIndexes as $index) {
            $indexes[] = 'CREATE UNIQUE INDEX ' . $index['CONSTRAINT_NAME'] . ' ON ' .
                $index['TABLE_NAME'] . ' (' . $index['COLUMN_NAME'] . ');';
        }
        return $indexes;
    }

    private function compileSecondaryIndexes(): array
    {
        $indexes = [];
        foreach ($this->secondaryIndexes as $index) {
            $indexName = ucfirst($this->tableName) . 'By' . ucfirst($index);
            $indexes[] = 'CREATE INDEX ' . $indexName . ' ON ' .
                $this->tableName . '(' . $index . ');';
        }
        return $indexes;
    }
}
