## MySQL Parser to Google Cloud Spanner for PHP

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

## Usage Instructions

To use the library, you will need an array of the columns from
MySQL and the respective foreign keys / indexes.

The table array you can use the `Describe` from MySQL, the foreign keys you will need to do something like [that](https://dev.mysql.com/doc/refman/8.0/en/information-schema-key-column-usage-table.html).

```PHP
use MgCosta\MysqlParser\Parser;

$schemaParser = new Parser();

$tableName = 'users';

$table = [
    [
        'Field' => 'id',
        'Type' => 'biginteger unsigned',
        'Null' => 'NO',
        'Key' => 'PRI',
        'Default' => null,
        'Extra' => 'auto_increment'
    ],
    [
        'Field' => 'name',
        'Type' => 'varchar(255)',
        'Null' => 'NO',
        'Key' => '',
        'Default' => null,
        'Extra' => ''
    ],
    [
        'Field' => 'email',
        'Type' => 'varchar(255)',
        'Null' => 'NO',
        'Key' => 'UNI',
        'Default' => null,
        'Extra' => ''
    ],
    [
        'Field' => 'password',
        'Type' => 'varchar(255)',
        'Null' => 'NO',
        'Key' => '',
        'Default' => null,
        'Extra' => ''
    ]
];

$keys = [
    [
        'TABLE_NAME' => 'users',
        'COLUMN_NAME' => 'id',
        'CONSTRAINT_NAME' => 'PRIMARY',
        'REFERENCED_TABLE_NAME' => null,
        'REFERENCED_COLUMN_NAME' => null
    ],
    [
        'TABLE_NAME' => 'users',
        'COLUMN_NAME' => 'email',
        'CONSTRAINT_NAME' => 'users_email_unique',
        'REFERENCED_TABLE_NAME' => null,
        'REFERENCED_COLUMN_NAME' => null
    ]
];

$ddl = $schemaParser->setTableName($tableName)
                    ->setDescribedTable($table)
                    ->setKeys($keys)
                    ->toDDL();
                    
// it will output an array of DDL statements required to create
// the necessary elements to compose the table
// -------------------------------------------
// array(2) {
//  [0]=> string(145) "CREATE TABLE users (
//      id INT64 NOT NULL,
//      name STRING(255) NOT NULL,
//      email STRING(255) NOT NULL,
//      password STRING(255) NOT NULL
//  ) PRIMARY KEY (id)"
//  [1]=> string(55) "CREATE UNIQUE INDEX users_email_unique ON users (email)"
```

The library outputs an array of valid and required statements
to insert on the Google Cloud Spanner engine.

### Using the Grammar Service for MySQL

To help your life to fetch the data we need from MySQL to
create the spanner statements, you can use the available service
on your **PDO** or **ORM / Query Builder**:

This will simply generate a valid query string which you
can use to fetch the columns & keys details.

The example below is **[Laravel](https://laravel.com/docs/8.x/queries)** based, but you can adapt it easily.

```PHP
use Illuminate\Support\Facades\DB;
use MgCosta\MysqlParser\Parser;
use MgCosta\MysqlParser\Grammar;

$schemaParser = new Parser();
$mysqlGrammar = new Grammar();

$tableName = 'users';

// you can extract the table details doing the following
$table = DB::select(
    DB::raw($mysqlGrammar->getTableDetails($tableName))
);
$keys = DB::select(
    DB::raw($mysqlGrammar->getTableKeysDetails($tableName))
);

$ddl = $schemaParser->setTableName($tableName)
                    ->setDescribedTable($table)
                    ->setKeys($keys)
                    ->toDDL();
```


## Roadmap

You can get more details of the plans for this early version on the following [link](https://github.com/mgcostaParedes/php-mysql-to-cloud-spanner/projects/1).

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Miguel Costa][https://github.com/mgcostaParedes]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
