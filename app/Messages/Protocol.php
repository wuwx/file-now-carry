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
        $m->data = intval($object->data ?? []);
        return $m;
    }


    public static function newInstanceToJson($type = null, $msg = null, $data = null)
    {
        $object = new self($type, $msg, $data);
        return $object->toJson();
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
}