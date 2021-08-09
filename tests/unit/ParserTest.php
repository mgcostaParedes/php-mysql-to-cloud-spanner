<?php

namespace Tests\unit;

use Codeception\Test\Unit;
use MgCosta\MysqlParser\Contracts\Processable;
use MgCosta\MysqlParser\Exceptions\ParserException;
use MgCosta\MysqlParser\Parser;
use Mockery as m;
use RuntimeException;

class ParserTest extends Unit
{
    private $parser;
    private $spannerProcessor;
    private $tableName = 'test';

    private $defaultPrimaryKey = [
        'Field' => 'id',
        'Type' => 'integer',
        'Null' => 'NO',
        'Key' => 'PRI',
        'Default' => null,
        'Extra' => ''
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->spannerProcessor = m::mock(Processable::class);
        $this->parser = new Parser($this->spannerProcessor);
    }

    public function testShouldThrowARuntimeExceptionWhenSettingTableWithInvalidKeyNames()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("There's invalid column keys for the described table");

        $fields = [
            [
                'Field' => 'airline',
                'Error' => 'varchar(255)',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];

        $this->parser->setDescribedTable($fields);
    }

    public function testShouldThrowARuntimeExceptionWhenSettingKeysWithInvalidNames()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("There's invalid column keys for the described keys");

        $keys = [
            [
                'TABLE_NAME' => 'flights',
                'COLUMN_NAME_ERROR' => 'id',
                'CONSTRAINT_NAME' => 'PRIMARY',
                'REFERENCED_TABLE_NAME' => null,
                'REFERENCED_COLUMN_NAME' => null
            ],
        ];

        $this->parser->setKeys($keys);
    }

    public function testShouldGetPropertiesWhenSet()
    {
        $this->parser->setTableName($this->tableName);

        $fields = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'airline',
                'Type' => 'varchar(255)',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];
        $keys = [
            [
                'TABLE_NAME' => 'flights',
                'COLUMN_NAME' => 'id',
                'CONSTRAINT_NAME' => 'PRIMARY',
                'REFERENCED_TABLE_NAME' => null,
                'REFERENCED_COLUMN_NAME' => null
            ]
        ];

        $this->parser->setTableName($this->tableName)->setDescribedTable($fields)->setKeys($keys);

        $this->assertEquals($this->tableName, $this->parser->getTableName());
        $this->assertEquals($keys, $this->parser->getDescribedKeys());
        $this->assertEquals($fields, $this->parser->getDescribedTable());
    }

    public function testShouldParsePropertiesToArrayWhenAnEntryIsAnObject()
    {
        $this->parser->setTableName($this->tableName);

        $fields = [
            (object)$this->defaultPrimaryKey,
            [
                'Field' => 'airline',
                'Type' => 'varchar(255)',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];
        $keys = [
            [
                'TABLE_NAME' => 'flights',
                'COLUMN_NAME' => 'id',
                'CONSTRAINT_NAME' => 'PRIMARY',
                'REFERENCED_TABLE_NAME' => null,
                'REFERENCED_COLUMN_NAME' => null
            ]
        ];

        $this->parser->setTableName($this->tableName)->setDescribedTable($fields)->setKeys($keys);

        $this->assertEquals($this->tableName, $this->parser->getTableName());
        $this->assertEquals($keys, $this->parser->getDescribedKeys());

        $fieldsParsedToArray = $fields;
        $fieldsParsedToArray[0] = (array)$fieldsParsedToArray[0];
        $this->assertEquals($fieldsParsedToArray, $this->parser->getDescribedTable());
    }

    public function testShouldParseAMysqlBuilderSuccessfully()
    {
        $fields = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'airline',
                'Type' => 'varchar(255)',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];
        $keys = [
            [
                'TABLE_NAME' => 'flights',
                'COLUMN_NAME' => 'id',
                'CONSTRAINT_NAME' => 'PRIMARY',
                'REFERENCED_TABLE_NAME' => null,
                'REFERENCED_COLUMN_NAME' => null
            ]
        ];

        $expectedDDL = 'CREATE TABLE flights (' . PHP_EOL .
        'id INT64 NOT NULL,' . PHP_EOL .
        'airline STRING(255)' . PHP_EOL .
        ') PRIMARY KEY (id)';

        $this->spannerProcessor->shouldReceive('parseDescribedSchema')
                            ->andReturn([$expectedDDL])->once();

        $ddl = $this->parser->setTableName($this->tableName)->setDescribedTable($fields)->setKeys($keys)->toDDL();
        $this->assertEquals([ $expectedDDL ], $ddl);
    }

    public function testShouldThrowAParserExceptionWhenCallingParseWithoutSettingRequiredProperties()
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage("You must define a described table to parse");

        $this->parser->setTableName($this->tableName)->toDDL();
    }
}
