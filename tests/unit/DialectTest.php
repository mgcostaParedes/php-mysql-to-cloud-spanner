<?php

namespace Tests\unit;

use Codeception\Test\Unit;
use MgCosta\MysqlParser\Dialect;

class DialectTest extends Unit
{
    private $describer;
    private $table = 'test';
    private $database = 'test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->describer = new Dialect();
    }

    public function testShouldGetDescribedTableColumnsSyntax()
    {
        $this->assertEquals(
            'DESCRIBE ' . $this->table,
            $this->describer->generateTableDetails($this->table)
        );
    }

    public function testShouldGetDescribedTableKeysSyntax()
    {
        $this->assertEquals(
            "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = '" . $this->table .
                        "' AND CONSTRAINT_SCHEMA = '" . $this->database . "'",
            $this->describer->generateTableKeysDetails($this->database, $this->table)
        );
    }
}
