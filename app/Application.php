<?php


namespace App;


use App\Contracts\EncrypterContract;
use App\Enums\MessageTypeEnum;
use App\Enums\RFC6455;
use App\Messages\Protocol;
use App\Utils\Str;
use MongoDB\BSON\Type;
use Swoole\Atomic;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Table;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class Application
{
    protected $idTable;
    protected $dataTable;

    protected $atomic;
    protected $server;

    protected $max;

    protected $encrypter;


    public function __construct(EncrypterContract $encrypter, $max = 100)
    {
        $this->encrypter = $encrypter;
        $this->server = new SocketServer();

        $this->max = $max;
        $this->atomic = new Atomic(0);


        $this->idTable = new Table($max);
        $this->dataTable = new Table($max);

        $this->idTable->column('uri', Table::TYPE_STRING, 32);

        // 反向索引id， 连接人数
        $this->dataTable->column('id', Table::TYPE_INT);
        $this->dataTable->column('links_count', Table::TYPE_INT);

        $this->idTable->create();
        $this->dataTable->create();
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
                    $this->dataTable->set($uri, ['id' => $frame->fd, 'links_count' => 0]);
                    break;
            }

            shell_exec("clear");


            foreach ($this->dataTable as $uri => $row) {

                echo $uri . '   ' . $row['id'] . '  ' . $row['links_count'] . PHP_EOL;
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
            $this->table->del($this->encrypter->decrypt($fd));
        };
    }

}