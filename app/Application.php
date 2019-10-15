<?php


namespace App;


use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Swoole\WebSocket\Frame;

class Application
{

    protected $server;


    public function __construct()
    {
        $this->server = new SocketServer();
    }

    public function run()
    {
        $this->server->onOpen([$this, 'onOpenEvent']);
        $this->server->onMessage([$this, 'onMessageEvent']);
        $this->server->onRequest([$this, 'onRequestEvent']);
        $this->server->onClose([$this, 'onCloseEvent']);


        $this->server->start();
    }

    private function onOpenEvent(Server $server, Request $request)
    {

    }

    private function onMessageEvent(Server $server, Frame $frame)
    {

    }

    private function onRequestEvent(Request $request, Response $response)
    {

    }

    private function onCloseEvent(Server $server, int $fd, int $reactorId)
    {

    }

}