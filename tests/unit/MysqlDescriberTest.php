<?php

namespace Tests\unit;

use Codeception\Test\Unit;
use MgCosta\MysqlParser\Describer\MysqlDescriber;

class MysqlDescriberTest extends Unit
{
    private $describer;
    private $table = 'test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->describer = new MysqlDescriber();
    }

    public function testShouldGetDescribedTableColumnsSyntax()
    {
        $this->assertEquals(
            'DESCRIBE ' . $this->table,
            $this->describer->getTableDetails($this->table)
        );
    }

    public function testShouldGetDescribedTableKeysSyntax()
    {
        $this->assertEquals(
            "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = '" . $this->table . "'",
            $this->describer->getTableKeysDetails($this->table)
        );
    }
}
