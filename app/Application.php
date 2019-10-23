<?php


namespace App;


use App\Contracts\EncrypterContract;
use App\Enums\MessageTypeEnum;
use App\Enums\RFC6455;
use App\Messages\Protocol;
use App\Models\Admin;
use App\Models\User;
use App\Models\UserHasFile;
use App\Utils\Arr;
use App\Utils\Str;
use Swoole\Atomic;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Table;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class Application
{
    public $userTable;
    public $fileTable;
    public $adminTable;

    protected $atomic;
    protected $server;

    protected $max;


    public function __construct($max = 100, $port = 9999)
    {
        $this->server = new SocketServer('0.0.0.0', $port);

        $this->max = $max;
        $this->atomic = new Atomic(0);


        // 创建内存表
        $this->userTable = User::createTable(new Table($max));
        $this->fileTable = UserHasFile::createTable(new Table($max));
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

            $this->userTable->set($request->fd, User::newInstance(['id' => $request->fd])->toArray());
        };
    }

    private function onMessageEvent()
    {
        return function (Server $server, Frame $frame) {

            $message = Protocol::parse($frame->data);
            switch ($message->getType()) {

                case MessageTypeEnum::CREATED_ROOM:

                    // 如果已经建立过了，那么就回应
                    if ($this->fileTable->exist($frame->fd)) {

                        $server->push($frame->fd, Protocol::newInstanceToJson(MessageTypeEnum::CREATED_ROOM, '你已经创建了房间，请勿重复'));
                        return;
                    }

                    // 建立房间
                    // 1. 生成房间链接
                    $uri = Str::random();

                    $this->userTable->set($frame->fd, User::newInstance(['uri' => $uri])->toArray());
                    $this->fileTable->set($uri, UserHasFile::newInstance(array_merge(['userId' => $frame->fd], $message->getData()))->toArray());
                    break;


                // 后台的请求
                case MessageTypeEnum::ADMIN_EVENT_INIT_DATA:

                    $data = [
                        'users' => Arr::getTableRows($this->userTable),
                        'files' => Arr::getTableRows($this->fileTable),
                    ];
                    $server->push($frame->fd, Protocol::newInstanceToJson(MessageTypeEnum::ADMIN_EVENT_INIT_DATA, 'success', $data));
                    break;
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

            // 清除内存表
            if ($this->userTable->exists($fd)) {

                $user = User::newInstance($this->userTable->get($fd));

                $this->userTable->del($user->getId());
                if (! is_null($user->getShareLink())) {

                    $this->fileTable->del($user->getShareLink());
                }
            }
        };
    }
}