<?php


namespace App;


use App\Contracts\EncrypterContract;
use App\Enums\MessageTypeEnum;
use App\Enums\RFC6455;
use App\Messages\Protocol;
use App\Models\User;
use App\Models\UserHasFile;
use App\Utils\Str;
use Swoole\Atomic;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Table;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class Application
{
    protected $userTable;
    protected $fileTable;

    protected $atomic;
    protected $server;

    protected $max;


    public function __construct($max = 100, $port = 9999)
    {
        $this->server = new SocketServer('0.0.0.0', $port);

        $this->max = $max;
        $this->atomic = new Atomic(0);


        $this->userTable = User::buildTable(new Table($max))->create();
        $this->fileTable = UserHasFile::buildTable(new Table($max))->create();
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

            if ($count >= $this->max) {

                echo "拒绝";
                $server->disconnect($request->fd, RFC6455::CLOSE_CODE, Protocol::newInstanceToJson(MessageTypeEnum::CLOSE, "连接数超过了{$this->max}, 拒绝连接"));
                return;
            }

            echo $request->fd . ' 连接服务器' . PHP_EOL;
        };
    }

    private function onMessageEvent()
    {
        return function (Server $server, Frame $frame) {

            echo $frame->data . PHP_EOL;

            $message = Protocol::parse($frame->data);
            switch ($message->getType()) {

                case MessageTypeEnum::CREATED_ROOM:

                    // 如果已经建立过了，那么就回应
                    if ($this->idTable->exist($frame->fd)) {

                        echo "已经建立过房间";
                        $server->push($frame->fd, Protocol::newInstanceToJson(MessageTypeEnum::CREATED_ROOM, '你已经创建了房间，请勿重复'));
                        return;
                    }

                    // 建立房间
                    // 1. 生成房间链接
                    $uri = Str::random();
                    $this->idTable->set($frame->fd, compact('uri'));
                    $values = array_merge(['id' => $frame->fd, 'links_count' => 0], $message->getData());
                    $this->dataTable->set($uri, $values);
                    break;
            }


            foreach ($this->dataTable as $uri => $row) {

                echo $uri . '   ' . $row['id'] . '  ' . $row['links_count'] .  '  ' . $row['file_name'] .PHP_EOL;
            }

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

            $this->atomic->sub(1);
            // 清楚数据表
            $user = $this->idTable->get($fd);

            $this->idTable->del($fd);
            $this->dataTable->del($user['uri']);
        };
    }

}