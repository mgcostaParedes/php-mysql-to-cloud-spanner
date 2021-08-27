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
     * The assigned names for unique keys
     *
     * @var array
     */
    private $assignedUniqueKeys = [];

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
    public const MAX_STRING_LENGTH = 2621440;

    /**
     * The maximum length for bytes to cloud spanner
     *
     * @var int
     */
    public const MAX_BYTES_LENGTH = 10485760;

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
        'tinyint',
        'intunsigned',
        'tinyintunsigned',
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
     * The described keys from MySQL
     *
     * @var string[]
     */
    private $keys;

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
        $this->keys = $builder->getDescribedKeys();
        $this->assignKeys();

        $tableDDL = 'CREATE TABLE `' . $this->tableName . '` (' . PHP_EOL;

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

            // if there's no method available and comes from unsigned, attribute the default method
            if (!method_exists($this, $method) && substr($method, -8) === 'unsigned') {
                $method = substr($method, 0, -8);
            }

            $tableDDL .= $this->$method($column);
        }

        $tableDDL .= PHP_EOL . ') PRIMARY KEY (' . implode(",", $this->primaryKeys) . ");";

        $indexes = array_merge($this->compileUniqueIndexes(), $this->compileSecondaryIndexes());
        $constraints = $this->compileForeignKeys();

        // reset the table details on object before returns the ddl
        $this->flush();

        return [
            'tables' => [ $tableDDL ],
            'indexes' => $indexes,
            'constraints' => $constraints
        ];
    }

    public function flush(): void
    {
        $this->tableName = null;
        $this->columns = null;
        $this->primaryKeys = [];
        $this->secondaryIndexes = [];
        $this->foreignKeys = [];
        $this->uniqueIndexes = [];
        $this->assignedUniqueKeys = [];
    }

    private function compileChar(array $column): string
    {
        return $this->compileVarchar($column);
    }

    private function compileVarchar(array $column): string
    {
        $stringCol = 'STRING(';
        // if it has no details assign default size as 255
        $stringCol .= !empty($column['Details']) ? $column['Details'][1] : '255';
        $stringCol .= ')';

        return $this->setNewColumn($column, $stringCol);
    }

    private function compileDecimal(array $column): string
    {
        return $this->setNewColumn($column, 'NUMERIC');
    }

    private function compileDouble(array $column): string
    {
        return $this->compileFloat($column);
    }

    private function compileFloat(array $column): string
    {
        return $this->setNewColumn($column, 'FLOAT64');
    }

    private function compileBool(array $column): string
    {
        return $this->compileBoolean($column);
    }

    private function compileBoolean(array $column): string
    {
        return $this->setNewColumn($column, 'BOOL');
    }

    private function compileInteger(array $column): string
    {
        return $this->setNewColumn($column, 'INT64');
    }

    private function compileDate(array $column): string
    {
        return $this->setNewColumn($column, 'DATE');
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
        return $this->setNewColumn($column, 'TIMESTAMP', $options);
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
        return $this->setNewColumn($column, 'ARRAY<STRING(255)>');
    }

    private function compileTinytext(array $column): string
    {
        return $this->compileVarchar($column);
    }

    private function compileText(array $column): string
    {
        return $this->setNewColumn($column, 'STRING(65535)');
    }

    private function compileMediumtext(array $column): string
    {
        return $this->compileLongtext($column);
    }

    private function compileLongtext(array $column): string
    {
        return $this->setNewColumn($column, 'STRING(' . self::MAX_STRING_LENGTH . ')');
    }

    private function compileUnavailableTypes(array $column): string
    {
        return $this->setNewColumn($column, 'STRING(1000)');
    }

    private function compileBlob(array $column): string
    {
        $numOfBytes = !empty($column['Details']) ? (int)$column['Details'][1] : 65535;
        return $this->setBytesColumn($column, $numOfBytes);
    }

    private function compileTinyblob(array $column): string
    {
        $numOfBytes = !empty($column['Details']) ? (int)$column['Details'][1] : 255;
        return $this->setBytesColumn($column, $numOfBytes);
    }

    private function compileMediumblob(array $column): string
    {
        return $this->compileBinary($column);
    }

    private function compileLongblob(array $column): string
    {
        return $this->compileBinary($column);
    }

    private function compileVarbinary(array $column): string
    {
        return $this->compileBinary($column);
    }

    private function compileBinary(array $column): string
    {
        $numOfBytes = !empty($column['Details']) ? (int)$column['Details'][1] : self::MAX_BYTES_LENGTH;
        return $this->setBytesColumn($column, $numOfBytes);
    }

    private function compileJson(array $column): string
    {
        return $this->compileLongtext($column);
    }

    private function setBytesColumn(array $column, int $numOfBytes): string
    {
        return $this->setNewColumn($column, 'BYTES(' . $numOfBytes . ')');
    }

    private function setNewColumn(array $column, string $type, string $options = null): string
    {
        return '`' . $column['Field'] . '` ' . $type . $this->resolveAppends($column, $options);
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

    protected function assignKeys(): void
    {
        foreach ($this->columns as $column) {
            if (!empty($column['Key'])) {
                $this->assignColumnKey($column['Field'], $column['Key']);
            }
        }

        // if no primary key founded on table, we should assign a default id column
        if (empty($this->primaryKeys)) {
            $this->primaryKeys[] = Dialect::DEFAULT_PRIMARY_KEY;
            array_unshift($this->columns, Dialect::DEFAULT_PRIMARY_KEY_PROPS);
        }
    }

    protected function assignColumnKey(string $field, string $type): void
    {
        if (!in_array($type, $this->availableMysqlKeys, true)) {
            throw new InvalidArgumentException('Column key type must be "PRI", "MUL" or "UNI"');
        }

        $fieldArrayIndex = array_search($field, array_column($this->keys, 'COLUMN_NAME'));

        if ($fieldArrayIndex === false && $type !== 'PRI' && $type !== 'MUL') {
            throw new InvalidArgumentException('Details for the key ' . $field . ' not found, provide it on "setKeys"');
        }

        if ($type === 'PRI') {
            $this->primaryKeys[] = $field;
            return;
        }

        $keyDetails = [];
        if ($fieldArrayIndex !== false) {
            $keyDetails = $this->keys[$fieldArrayIndex];
        }

        if ($type === 'MUL' && !empty($keyDetails) && !is_null($keyDetails['REFERENCED_TABLE_NAME'])) {
            $this->foreignKeys[] = $keyDetails;
        }

        if ($type === 'MUL' && empty($keyDetails)) {
            $this->secondaryIndexes[] = $field;
        }

        if (
            ($type === 'UNI') ||
            ($type === 'MUL' && !empty($keyDetails) && is_null($keyDetails['REFERENCED_TABLE_NAME']))
        ) {
            $this->uniqueIndexes[] = $keyDetails;
        }
    }

    private function compileForeignKeys(): array
    {
        $constraints = [];
        foreach ($this->foreignKeys as $foreign) {
            $constraints[] = 'ALTER TABLE `' . $this->tableName . '` ADD CONSTRAINT `' .
                $foreign['CONSTRAINT_NAME'] . '` FOREIGN KEY (`' .
                $foreign['COLUMN_NAME'] . '`)' . ' REFERENCES `' .
                $foreign['REFERENCED_TABLE_NAME'] .  '` (`' .
                $foreign['REFERENCED_COLUMN_NAME'] . '`);';
        }
        return $constraints;
    }

    private function compileUniqueIndexes(): array
    {
        $indexes = [];
        foreach ($this->uniqueIndexes as $index) {
            if (in_array($index['CONSTRAINT_NAME'], $this->assignedUniqueKeys)) {
                continue;
            }
            // check multiple column unique indexes
            $multipleColumnKey = array_filter($this->keys, function ($key) use ($index) {
                return ($key['CONSTRAINT_NAME'] === $index['CONSTRAINT_NAME']);
            });

            if (!empty($multipleColumnKey)) {
                $columnNames = [];
                foreach ($multipleColumnKey as $key) {
                    $columnNames[] = $key['COLUMN_NAME'];
                }
                $index['COLUMN_NAME'] = implode('`, `', $columnNames);
            }

            // to prevent duplicated unique keys
            $this->assignedUniqueKeys[] = $index['CONSTRAINT_NAME'];

            $indexes[] = 'CREATE UNIQUE INDEX `' . $index['CONSTRAINT_NAME'] . '` ON `' .
                $index['TABLE_NAME'] . '` (`' . $index['COLUMN_NAME'] . '`);';
        }
        return $indexes;
    }

    private function compileSecondaryIndexes(): array
    {
        $indexes = [];
        foreach ($this->secondaryIndexes as $index) {
            $indexName = ucfirst($this->tableName) . 'By' . ucfirst($index);
            $indexes[] = 'CREATE INDEX `' . $indexName . '` ON `' .
                $this->tableName . '` (`' . $index . '`);';
        }
        return $indexes;
    }
}
