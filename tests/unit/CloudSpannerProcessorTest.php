<?php

namespace Tests\unit;

use Codeception\Test\Unit;
use MgCosta\MysqlParser\Parser;
use MgCosta\MysqlParser\Processor\CloudSpanner;
use Mockery as m;

class CloudSpannerProcessorTest extends Unit
{
    private $processor;
    private $parserBuilder;
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
        $this->processor = new CloudSpanner();
        $this->parserBuilder = m::mock(Parser::class);
    }

    public function testShouldCompileVarcharSuccessfully()
    {
        $field = [
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

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('airline STRING(255)', $sql);
    }

    public function testShouldCompileCharSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'airline',
                'Type' => 'char(100)',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('airline STRING(100)', $sql);
    }

    public function testShouldCompileIntegerSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'value',
                'Type' => 'integer',
                'Null' => 'NO',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('value INT64 NOT NULL', $sql);
    }

    public function testShouldCompileAMysqlTypeToIntegerSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'value',
                'Type' => 'biginteger unsigned',
                'Null' => 'NO',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('value INT64 NOT NULL', $sql);
    }

    public function testShouldCompileDecimalSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'price',
                'Type' => 'decimal(10,4)',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('price NUMERIC', $sql);
    }

    public function testShouldCompileDoubleAndFloatSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'price',
                'Type' => 'double(8,4)',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('price FLOAT64', $sql);
    }

    public function testShouldCompileBooleanSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'active',
                'Type' => 'tinyint(1)',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('active BOOL', $sql);
    }

    public function testShouldCompileBoolSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'active',
                'Type' => 'bool',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('active BOOL', $sql);
    }

    public function testShouldCompileDateSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'created_at',
                'Type' => 'date',
                'Null' => 'NO',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('created_at DATE NOT NULL', $sql);
    }

    public function testShouldCompileDateTimeAndTimestampSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'created_at',
                'Type' => 'datetime',
                'Null' => 'NO',
                'Key' => '',
                'Default' => 'CURRENT_TIMESTAMP',
                'Extra' => 'DEFAULT_GENERATED'
            ],
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('created_at TIMESTAMP NOT NULL OPTIONS (allow_commit_timestamp=true)', $sql);
    }

    public function testShouldCompileEnumSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'difficulty',
                'Type' => "enum('easy', 'hard', 'medium')",
                'Null' => 'NO',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ],
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('difficulty STRING(255) NOT NULL', $sql);
    }

    public function testShouldCompileSetSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'flavors',
                'Type' => "set('strawberry','vanilla')",
                'Null' => 'NO',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ],
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('flavors ARRAY<STRING(255)> NOT NULL', $sql);
    }

    public function testShouldCompileTinyTextSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'text',
                'Type' => 'tinytext(155)',
                'Null' => 'NO',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ],
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('text STRING(155) NOT NULL', $sql);
    }

    public function testShouldCompileTextSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'text',
                'Type' => 'text',
                'Null' => 'NO',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ],
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('text STRING(65535) NOT NULL', $sql);
    }

    public function testShouldCompileMediumAndLongTextSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'text',
                'Type' => 'mediumtext',
                'Null' => 'NO',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ],
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('text STRING(2621440) NOT NULL', $sql);
    }

    public function testShouldCompileUnavailableSpannerTypeSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'location',
                'Type' => 'geometry',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ],
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('location STRING(1000)', $sql);
    }

    public function testShouldCompileYearSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'year',
                'Type' => 'year',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ],
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('year STRING(4)', $sql);
    }

    public function testShouldCompileTimeSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'hour',
                'Type' => 'time',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ],
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('hour STRING(50)', $sql);
    }

    public function testShouldCompileJsonSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'details',
                'Type' => 'json',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ],
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('details STRING(2621440)', $sql);
    }

    public function testShouldCompileBlobTypeSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'photo',
                'Type' => "blob",
                'Null' => 'YES',
                'Key' => "",
                'Default' => null,
                'Extra' => ''
            ],
        ];

        $this->setupParserMocksWithoutIndexes($field);

        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertDefaultTable('photo BYTES(10485760)', $sql);
    }

    public function testShouldThrowAnInvalidArgumentExceptionWhenAssigningInvalidKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $field = [
            [
                'Field' => 'airline',
                'Type' => 'varchar(255)',
                'Null' => 'YES',
                'Key' => "ERROR",
                'Default' => null,
                'Extra' => ''
            ]
        ];

        $this->setupParserMocksWithoutIndexes($field);
        $this->processor->parseDescribedSchema($this->parserBuilder);
    }

    public function testShouldThrowAnInvalidArgumentExceptionWhenAssigningUNIKeyWithoutDescribed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $field = [
            [
                'Field' => 'id_travel',
                'Type' => 'int unsigned',
                'Null' => 'NO',
                'Key' => "UNI",
                'Default' => null,
                'Extra' => ''
            ]
        ];

        $this->setupParserMocksWithoutIndexes($field);
        $this->processor->parseDescribedSchema($this->parserBuilder);
    }

    public function testShouldCompileWithASecondaryIndexWhenAssigningMULKeyWithoutDescribedKey()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'id_travel',
                'Type' => 'int unsigned',
                'Null' => 'NO',
                'Key' => "MUL",
                'Default' => null,
                'Extra' => ''
            ]
        ];

        $this->setupParserMocksWithoutIndexes($field, []);
        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertEquals('CREATE INDEX TestById_travel ON test(id_travel)', $sql[1]);
    }

    public function testShouldCompileAForeignKeySuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'id_travel',
                'Type' => 'int unsigned',
                'Null' => 'NO',
                'Key' => "MUL",
                'Default' => null,
                'Extra' => ''
            ]
        ];

        $key = [
            [
                'TABLE_NAME' => $this->tableName,
                'COLUMN_NAME' => 'id_travel',
                'CONSTRAINT_NAME' => $this->tableName . '_id_travel_foreign',
                'REFERENCED_TABLE_NAME' => 'travels',
                'REFERENCED_COLUMN_NAME' => 'id'
            ]
        ];

        $this->setupParserMocksWithoutIndexes($field, $key);
        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertEquals(
            [ 'CREATE TABLE ' . $this->tableName . ' (' . PHP_EOL .
            'id INT64 NOT NULL,' . PHP_EOL .
            'id_travel INT64 NOT NULL,' . PHP_EOL .
            'CONSTRAINT test_id_travel_foreign FOREIGN KEY (id_travel) REFERENCES travels (id)' . PHP_EOL .
            ') PRIMARY KEY (id)' ],
            $sql
        );
    }

    public function testShouldCompileAnUniqueIndexSuccessfully()
    {
        $field = [
            $this->defaultPrimaryKey,
            [
                'Field' => 'email',
                'Type' => 'varchar(255)',
                'Null' => 'NO',
                'Key' => 'UNI',
                'Default' => null,
                'Extra' => ''
            ]
        ];

        $key = [
            [
                'TABLE_NAME' => $this->tableName,
                'COLUMN_NAME' => 'email',
                'CONSTRAINT_NAME' => $this->tableName . '_email_unique',
                'REFERENCED_TABLE_NAME' => null,
                'REFERENCED_COLUMN_NAME' => null
            ]
        ];

        $this->setupParserMocksWithoutIndexes($field, $key);
        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertEquals(
            [
                'CREATE TABLE ' . $this->tableName . ' (' . PHP_EOL .
                'id INT64 NOT NULL,' . PHP_EOL .
                'email STRING(255) NOT NULL' . PHP_EOL .
                ') PRIMARY KEY (id)',
                'CREATE UNIQUE INDEX ' . $this->tableName . '_email_unique ON ' . $this->tableName . ' (email)'
            ],
            $sql
        );
    }

    public function testShouldCompileADescribedTableWithoutPKSuccessfullyWithDefaultPrimaryKey()
    {
        $field = [
            [
                'Field' => 'email',
                'Type' => 'varchar(255)',
                'Null' => 'NO',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];
        $this->setupParserMocksWithoutIndexes($field, []);
        $sql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertEquals(
            'CREATE TABLE ' . $this->tableName . ' (' . PHP_EOL .
            'id INT64 NOT NULL,' . PHP_EOL .
            'email STRING(255) NOT NULL' . PHP_EOL .
            ') PRIMARY KEY (id)',
            $sql[0]
        );
    }

    public function testShouldResetTheTableDetailsAndPreventDuplicateKeysWhenSchemaIsParsed()
    {
        $field = [
            [
                'Field' => 'email',
                'Type' => 'varchar(255)',
                'Null' => 'NO',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];
        $this->setupParserMocksWithoutIndexes($field, []);
        $firstSql = $this->processor->parseDescribedSchema($this->parserBuilder);

        $expectedDDL = 'CREATE TABLE ' . $this->tableName . ' (' . PHP_EOL .
            'id INT64 NOT NULL,' . PHP_EOL .
            'email STRING(255) NOT NULL' . PHP_EOL .
            ') PRIMARY KEY (id)';

        $this->assertEquals($expectedDDL, $firstSql[0]);

        $secondSql = $this->processor->parseDescribedSchema($this->parserBuilder);
        $this->assertEquals($expectedDDL, $secondSql[0]);
    }

    private function setupParserMocksWithoutIndexes(array $table, array $keys = [])
    {
        $this->parserBuilder->shouldReceive('setDescribedTable')
            ->andReturnSelf()->once();
        $this->parserBuilder->shouldReceive('getTableName')
            ->andReturn($this->tableName)->once();
        $this->parserBuilder->shouldReceive('getDescribedTable')
            ->andReturn($table)->once();
        $this->parserBuilder->shouldReceive('getDescribedKeys')
            ->andReturn($keys)->once();
    }

    private function assertDefaultTable(string $column, array $sql)
    {
        $this->assertEquals(
            [ 'CREATE TABLE ' . $this->tableName . ' (' . PHP_EOL .
            'id INT64 NOT NULL,' . PHP_EOL .
            $column . PHP_EOL .
            ') PRIMARY KEY (id)' ],
            $sql
        );
    }

}
