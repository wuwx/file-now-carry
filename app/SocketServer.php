<?php

namespace App;

use Swoole\WebSocket\Server;

class SocketServer extends Server
{
    public function __construct($host = '0.0.0.0', $port = '9999')
    {
        parent::__construct($host, $port);
    }


    public function onConnection($callback)
    {
        if ($callback instanceof \Closure) {

            $this->on('connection', $callback);
        }
    }

    public function onOpen($callback)
    {
        if ($callback instanceof \Closure) {

            $this->on('open', $callback);
        }
    }

    public function onClose($callback)
    {
        if ($callback instanceof \Closure) {

            $this->on('close', $callback);
        }
    }

    public function onRequest($callback)
    {
        if ($callback instanceof \Closure) {

            $this->on('request', $callback);
        }
    }


    public function onMessage($callback)
    {
        if ($callback instanceof \Closure) {

            $this->on('message', $callback);
        }
    }
}