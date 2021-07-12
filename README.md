## Mysql Parser to Google Cloud Spanner for PHP

[![License](http://poser.pugx.org/mgcosta/mysql-to-cloud-spanner/license)](https://packagist.org/packages/mgcosta/mysql-to-cloud-spanner)
[![Actions Status](https://github.com/mgcostaParedes/php-mysql-to-cloud-spanner/workflows/CI/badge.svg)](https://github.com/mgcostaParedes/php-mysql-to-cloud-spanner/actions)
[![codecov](https://codecov.io/gh/mgcostaParedes/php-mysql-to-cloud-spanner/branch/main/graph/badge.svg?token=L20N2UY9X6)](https://codecov.io/gh/mgcostaParedes/php-mysql-to-cloud-spanner)
[![Total Downloads](http://poser.pugx.org/mgcosta/mysql-to-cloud-spanner/downloads)](https://packagist.org/packages/mgcosta/mysql-to-cloud-spanner)


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
use MgCosta\MysqlParser\MysqlDescriber;


$parser = new Parser();
$describer = new MysqlDescriber();

$tableName = 'users';

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