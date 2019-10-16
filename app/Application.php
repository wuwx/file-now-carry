<?php


namespace App;


use App\Enums\MessageTypeEnum;
use App\Enums\RFC6455;
use App\Messages\Protocol;
use Swoole\Atomic;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Table;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class Application
{
    protected $table;
    protected $atomic;
    protected $server;

    protected $max;


    public function __construct($max = 100)
    {
        $this->server = new SocketServer();

        $this->max = $max;

        $this->atomic = new Atomic(0);
        $this->table = new Table($max);
    }

    public function run()
    {
        $this->server->onOpen($this->onOpenEvent());
        $this->server->onMessage($this->onMessageEvent());
        $this->server->onRequest($this->onRequestEvent());
        $this->server->onClose($this->onCloseEvent());

        $this->server->start();
    }

    private function onOpenEvent()
    {
        return function (Server $server, Request $request) {

            $count = $this->atomic->get();
            $this->atomic->add(1);

            echo "用户连接: " . $request->fd . PHP_EOL;
            $server->disconnect($request->fd, RFC6455::CLOSE_CODE, Protocol::newInstanceToJson(MessageTypeEnum::CLOSE, "连接数超过了{$this->max}, 拒绝连接"));
            return;
            if ($count >= $this->max) {

                $server->disconnect($request->fd, RFC6455::CLOSE_CODE, Protocol::newInstanceToJson(MessageTypeEnum::CLOSE, "连接数超过了{$this->max}, 拒绝连接"));
                return;
            }
        };
    }

    private function onMessageEvent()
    {
        return function (Server $server, Frame $frame) {

        };
    }

    private function onRequestEvent()
    {
        return function (Request $request, Response $response) {

        };
    }

    private function onCloseEvent()
    {
        return function (Server $server, int $fd, int $reactorId) {

            echo "拒绝连接: ". $fd. PHP_EOL;
            $this->atomic->sub(1);
        };
    }

}