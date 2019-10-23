<?php

namespace App\Utils;

use Swoole\Table;

class Str
{

    public static function random($len = 16)
    {
        $chars = array(
           "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
           "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
           "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
           "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
           "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
           "3", "4", "5", "6", "7", "8", "9"
       );
        $maxIndex = count($chars) - 1;


        $str = '';
        for ($i = 0; $i < $len; ++ $i) {

            $index = mt_rand(0, $maxIndex);
            $str .= $chars[$index];
        }

        return $str;
    }

    public static function caseAsSwooleType($type)
    {
        switch ($type) {

            case 'int':
            case 'integer':
                return Table::TYPE_INT;
                break;
            case 'str':
            case 'string':
                return Table::TYPE_STRING;
                break;
            case 'float':
            case 'double':
                return Table::TYPE_FLOAT;
                break;
        }

        return Table::TYPE_INT;
    }
}