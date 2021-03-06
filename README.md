## MySQL to Google Cloud Spanner Migration Tool for PHP

[![License](http://poser.pugx.org/mgcosta/mysql-to-cloud-spanner/license)](https://packagist.org/packages/mgcosta/mysql-to-cloud-spanner)
[![Actions Status](https://github.com/mgcostaParedes/php-mysql-to-cloud-spanner/workflows/CI/badge.svg)](https://github.com/mgcostaParedes/php-mysql-to-cloud-spanner/actions)
[![codecov](https://codecov.io/gh/mgcostaParedes/php-mysql-to-cloud-spanner/branch/main/graph/badge.svg?token=L20N2UY9X6)](https://codecov.io/gh/mgcostaParedes/php-mysql-to-cloud-spanner)
[![Total Downloads](http://poser.pugx.org/mgcosta/mysql-to-cloud-spanner/downloads)](https://packagist.org/packages/mgcosta/mysql-to-cloud-spanner)


The MySQL Parser to Google Cloud Spanner is a library for PHP, providing an easy way to migrate the data from MySQL to **Google Cloud Spanner**.

## Install

Via Composer

``` bash
$ composer require mgcosta/mysql-to-cloud-spanner
```

## Usage Instructions

To use this toolkit, you will need an array of the columns from
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
// array(3) {
//  ['tables'] => array {
//      [0] => string(145) "CREATE TABLE users (
//          id INT64 NOT NULL,
//          name STRING(255) NOT NULL,
//          email STRING(255) NOT NULL,
//          password STRING(255) NOT NULL
//      ) PRIMARY KEY (id)"
//  }
//  ['indexes'] => array {
//      [0] => string(55) "CREATE UNIQUE INDEX users_email_unique ON users (email)"
//  }
//  ['constraints'] => array {}
```

The library outputs a multidimensional array with following
keys '**tables**', '**indexes**', '**constraints**'
to insert on the Google Cloud Spanner engine.

**Note**: You may want to store the constraint keys to run
at the end of all tables and indexes to prevent running a
constraint for a table which is not created.

### Returning DDL statements without semicolons

If for some reason you need each statement without semicolon
at the end, you can use the method `shouldAssignPrimaryKey`:

```PHP

$schemaParser = (new Parser())->shouldAssignSemicolon(false);

$ddl = $schemaParser->setTableName($tableName)
                  ->setDescribedTable($table)
                  ->setKeys($keys)
                  ->toDDL();

```

### Dealing with schemas without Primary Keys

Since the primary key on cloud spanner is like almost [required](https://cloud.google.com/spanner/docs/schema-and-data-model#choosing_a_primary_key),
by default if there's a table schema without **PK** it will generate
a default column called by **id** which will be an **int64** type. However
you can modify the way this default column is created or disable it at all,
for that check the following example:

```PHP
use MgCosta\MysqlParser\Parser;
use MgCosta\MysqlParser\Dialect;
use MgCosta\MysqlParser\Exceptions\PrimaryKeyNotFoundException;

$schemaParser = new Parser();

$tableName = 'users';

$table = [
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
        'Key' => '',
        'Default' => null,
        'Extra' => ''
    ]
];

// define the default column id for a specific table
$ddl = $schemaParser->setDefaultID('column_id')
                  ->setTableName($tableName)
                  ->setDescribedTable($table)
                  ->setKeys($keys)
                  ->toDDL();

// disable the generation of default id
// it can lead on an exception

try {
    $schemaParser = (new Parser())->shouldAssignPrimaryKey(false);
    
    $ddl = $schemaParser->setTableName($tableName)
                    ->setDescribedTable($table)
                    ->setKeys($keys)
                    ->toDDL();

} catch(PrimaryKeyNotFoundException $e) {

}

```


### Using the Dialect Service for MySQL

To help your life to fetch the data we need from MySQL to
create the spanner statements, you can use the available service
on your **PDO** or **ORM / Query Builder**:

This will simply generate a valid query string which you
can use to fetch the columns & keys details.

The example below is **[Laravel](https://laravel.com/docs/8.x/queries)** based, but you can adapt it easily.

```PHP
use Illuminate\Support\Facades\DB;
use MgCosta\MysqlParser\Parser;
use MgCosta\MysqlParser\Dialect;

$schemaParser = new Parser();
$mysqlDialect = new Dialect();

$databaseName = 'my_database';
$tableName = 'users';

// you can extract the table details doing the following
$table = DB::select(
    DB::raw($mysqlDialect->generateTableDetails($tableName))
);
$keys = DB::select(
    DB::raw($mysqlDialect->generateTableKeysDetails($databaseName, $tableName))
);

$ddl = $schemaParser->setTableName($tableName)
                    ->setDescribedTable($table)
                    ->setKeys($keys)
                    ->toDDL();
```

### Prepare the data for migration

Sometimes you may have unexpected typed values to insert to Cloud Spanner,
when you fetch the data from **PHP PDOs** system's, for that you can use the
available transformer, which will prepare the data mapped with the described table.

For that follow the example below:

```PHP
use MgCosta\MysqlParser\Transformer\SpannerTransformer;

$table = [
    [
        'Field' => 'name',
        'Type' => 'varchar(255)',
        'Null' => 'NO',
        'Key' => '',
        'Default' => null,
        'Extra' => ''
    ],
    [
        'Field' => 'value',
        'Type' => 'decimal(8,2)',
        'Null' => 'NO',
        'Key' => '',
        'Default' => null,
        'Extra' => ''
    ]
];

$rows = [
     [
        'name' => 'product',
        'value' => '50.20'
    ]
];

$transformer = (new SpannerTransformer())->setDescribedTable($table);
$results = $transformer->setRows($rows)->transform();

```


## Roadmap

You can get more details of the plans for this early version on the following [link](https://github.com/mgcostaParedes/php-mysql-to-cloud-spanner/projects/1).

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Miguel Costa][https://github.com/mgcostaParedes]
- [Mike Slowik][https://github.com/sl0wik]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
