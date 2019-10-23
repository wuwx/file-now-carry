<?php

namespace App\Utils;

use Swoole\Table;

class Arr
{
    public static function getTableRows(Table $table)
    {
        $rows = [];

        foreach ($table as $row) {

            $rows[] = $row;
        }

        return $rows;
    }
}