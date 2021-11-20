<?php

declare(strict_types=1);

namespace MgCosta\MysqlParser\Traits;

trait TableColumnTrait
{
    /**
     * The array which will define all the detailed words from MySQL which can be removed
     *
     * @var string[]
     */
    protected $omitColumnDetailWords = [
        'unsigned',
    ];

    public function cleanTypeName(string $name, bool $omitWords = false): string
    {
        // remove extra details from the type
        $name = trim(preg_replace('/\s*\([^)]*\)/', '', $name));
        // remove white spaces from the type
        $name = preg_replace('/\s+/', '', $name);

        if ($omitWords) {
            $name = str_replace($this->omitColumnDetailWords, '', $name);
        }

        return $name;
    }
}
