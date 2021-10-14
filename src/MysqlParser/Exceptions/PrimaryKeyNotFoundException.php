<?php

namespace MgCosta\MysqlParser\Exceptions;

use Exception;

class PrimaryKeyNotFoundException extends Exception
{
    const MESSAGE = "There's no primary key available for the table!";
}
