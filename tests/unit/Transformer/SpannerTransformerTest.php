<?php

namespace Tests\unit;

use Codeception\Test\Unit;
use Google\Cloud\Spanner\Numeric;
use MgCosta\MysqlParser\Transformer\SpannerTransformer;

class SpannerTransformerTest extends Unit
{
    private $transformer;

    private $fields = [
        [
            'Field' => 'id',
            'Type' => 'integer',
            'Null' => 'NO',
            'Key' => 'PRI',
            'Default' => null,
            'Extra' => ''
        ],
        [
            'Field' => 'value',
            'Type' => 'decimal(8,2)',
            'Null' => 'YES',
            'Key' => '',
            'Default' => null,
            'Extra' => ''
        ]
    ];

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->transformer = new SpannerTransformer();
    }

    public function testShouldGetPropertiesWhenSet()
    {
        $rows = [
            (object) [
                'id' => 1,
                'value' => '50.10'
            ]
        ];

        $this->transformer->setDescribedTable($this->fields)->setRows($rows);

        $this->assertEquals($this->fields, $this->transformer->getDescribedTable());
        $this->assertEquals($rows, $this->transformer->getRows());
    }

    public function testShouldTransformAStringToAFloatFromDecimalTypeSuccessfullyWhenProvidingAMatchedArrayOfObjects()
    {
        $rows = [
            (object) [
                'id' => 1,
                'value' => '50.10'
            ]
        ];

        $this->transformer->setDescribedTable($this->fields)->setRows($rows);
        $rows = $this->transformer->transform();

        $this->assertInstanceOf(Numeric::class, $rows[0]->value);
    }

    public function testShouldTransformAStringToAFloatFromDecimalTypeSuccessfullyWhenProvidingAMatchedArrayOfArrays()
    {
        $rows = [
             [
                'id' => 1,
                'value' => '50.10'
            ]
        ];

        $this->transformer->setDescribedTable($this->fields)->setRows($rows);
        $rows = $this->transformer->transform();

        $this->assertInstanceOf(Numeric::class, $rows[0]['value']);
    }
}
