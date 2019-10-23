<?php

namespace App\Messages;

use App\Enums\MessageTypeEnum;

class Protocol
{
    protected $type;
    protected $msg;
    protected $data;

    public function __construct($type = null, $msg = null, $data = null)
    {
        $this->type = $type;
        $this->msg = $msg;
        $this->data = $data;
    }

    public static function parse($text)
    {
        $object = json_decode($text);

        $m = new self();
        $m->type = intval($object->type ?? MessageTypeEnum::COMMON);
        $m->msg = strval($object->msg ?? '');

        if (! isset($object->data)) {
            $m->data = [];
        } elseif (is_object($object->data)) {
            $m->data = (array)$object->data;
        } else {
            $m->data = $object->data;
        }

        return $m;
    }




    public function getType()
    {
        return $this->type;
    }

    public function getMessage()
    {
        return $this->msg;
    }

    public function getData()
    {
        return $this->data;
    }

    public function toJson()
    {
        $json = [
            'type' => intval($this->type ?? MessageTypeEnum::COMMON),
            'msg' => strval($this->msg ?? ''),
            'data' => $this->data ?? [],
        ];

        return json_encode($json, JSON_UNESCAPED_UNICODE);
    }

    public static function newInstanceToJson($type = null, $msg = null, $data = null)
    {
        $object = new self($type, $msg, $data);
        return $object->toJson();
    }
}