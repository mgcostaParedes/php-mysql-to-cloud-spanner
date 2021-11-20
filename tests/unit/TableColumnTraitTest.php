<?php

namespace Tests\unit;

use Codeception\Test\Unit;
use MgCosta\MysqlParser\Traits\TableColumnTrait;

class TableColumnTraitTest extends Unit
{
    private $trait;

    public function setUp(): void
    {
        parent::setUp();
        $this->trait = $this->getMockForTrait(TableColumnTrait::class);
    }

    public function testShouldCleanTypeNameWhenCalled(): void
    {
        $this->assertEquals('varchar', $this->trait->cleanTypeName('varchar(255)'));
    }

    public function testShouldCleanTypeAndRemoveDetailWords(): void
    {
        $this->assertEquals('decimal', $this->trait->cleanTypeName('decimal(4,2) unsigned', true));
    }
}
