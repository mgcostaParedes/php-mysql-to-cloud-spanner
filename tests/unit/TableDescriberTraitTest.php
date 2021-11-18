<?php

namespace Tests\unit;

use Codeception\Test\Unit;
use MgCosta\MysqlParser\Traits\TableDescriberTrait;
use RuntimeException;

class TableDescriberTraitTest extends Unit
{
    private $trait;

    public function setUp(): void
    {
        parent::setUp();
        $this->trait = $this->getMockForTrait(TableDescriberTrait::class);
    }

    public function testShouldValidateAndSetDescribedTable(): void
    {
        $fields = [
            (object)[
                'Field' => 'details',
                'Type' => 'varchar(255)',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];
        $this->assertEquals(null, $this->trait->validateAndSetDescribedTable($fields, 'test'));
    }

    public function testShouldThrowExceptionWhenProvideInvalidKeys(): void
    {
        $this->expectException(RuntimeException::class);
        $fields = [
            [
                'Fields' => 'details',
                'Type' => 'varchar(255)',
                'Null' => 'YES',
                'Key' => '',
                'Default' => null,
                'Extra' => ''
            ]
        ];
        $this->assertEquals(null, $this->trait->validateAndSetDescribedTable($fields, 'test'));
    }
}