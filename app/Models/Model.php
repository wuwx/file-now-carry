<?php

namespace App\Models;

use App\Utils\Str;
use DocBlockReader\Reader;
use Swoole\IDEHelper\StubGenerators\Swoole;
use Swoole\Table;


class Model
{
    /**
     * @param $attributes
     * @return static
     */
    public static function newInstance($attributes = [])
    {
        $model = new static();

        foreach ($attributes ?? [] as $key => $val) {

            if (property_exists($model, $key)) {

                $model->{$key} = $val;
            }
        }

        return $model;
    }

    public static function createTable(Table $table)
    {
        $className = static::class;
        $attributes = get_class_vars($className);

        foreach ($attributes as $attributeName => $val) {

            $reader = new Reader($className, $attributeName, 'property');

            $type = Str::caseAsSwooleType($reader->getParameter('var'));
            if ($type == Table::TYPE_STRING) {

                $maxLength = $reader->getParameter('length') ?? 50;
                $table->column($attributeName, $type, $maxLength);
            } else {

                $table->column($attributeName, $type);
            }
        }

        $table->create();
        return $table;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}