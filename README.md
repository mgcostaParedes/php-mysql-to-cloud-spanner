## Mysql Parser to Google Cloud Spanner for PHP

[![License](https://poser.pugx.org/mgcosta/spanner-orm-builder/license)](//packagist.org/packages/mgcosta/spanner-orm-builder)
[![Actions Status](https://github.com/mgcostaParedes/spanner-orm-builder/workflows/CI/badge.svg)](https://github.com/mgcostaParedes/spanner-orm-builder/actions)
[![codecov](https://codecov.io/gh/mgcostaParedes/spanner-orm-builder/branch/main/graph/badge.svg?token=OEUY7ZDTOP)](https://codecov.io/gh/mgcostaParedes/spanner-orm-builder)
[![Total Downloads](https://poser.pugx.org/mgcosta/spanner-orm-builder/downloads)](//packagist.org/packages/mgcosta/spanner-orm-builder)


The MySQL Parser to Google Cloud Spanner is a library for PHP, providing an easy way to transpile the "create tables" from MySQL to valid schemas for **Google Cloud Spanner** syntax.

## Install

Via Composer

``` bash
$ composer require mgcosta/mysql-to-cloud-spanner
```

### Usage Instructions

To use the library, you can follow the next example:

( the example is laravel based, but you can adapt easily 
for every framework )

```PHP

use Illuminate\Support\Facades\DB;
use MgCosta\MysqlParser\Parser;
use MgCosta\MysqlParser\Describer\MysqlDescriber;


$parser = new Parser();
$describer = new MysqlDescriber();

$tableName = 'flights';

// you can extract the table details doing the following
$table = DB::select(DB::raw($describer->getTableDetails($tableName)));
$keys = DB::select(DB::raw($describer->getTableKeysDetails($tableName)));

$spannerDDL = $parser->setTableName($tableName)
                    ->setDescribedTable($table)
                    ->setKeys($keys)
                    ->parse();
                    
// $spannerDDL will be the output to
// create the spanner table                    

```

## Roadmap

You can get more details of the plans for this early version on the following [link](https://github.com/mgcostaParedes/php-mysql-to-cloud-spanner/projects/1).

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Miguel Costa][https://github.com/mgcostaParedes]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.