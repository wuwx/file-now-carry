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
    protected $usersTable;
    protected $filesTable;
    protected $adminsTable;

    protected $atomic;
    protected $server;

    protected $max;
    protected $adminMaxCount = 9;


    public function __construct($max = 100, $port = 9999)
    {
        $this->server = new SocketServer('0.0.0.0', $port);

        $this->max = $max;
        $this->atomic = new Atomic(0);


        // 创建内存表
        $this->usersTable = User::createTable(new Table($max));
        $this->filesTable = UserHasFile::createTable(new Table($max));
        $this->adminsTable = Admin::createTable(new Table($this->adminMaxCount));
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

            $user = User::newInstance(['id' => $request->fd])->toArray();
            $this->usersTable->set($request->fd, $user);

            foreach ($this->adminsTable as $adminFd => $row) {

                $server->push($adminFd, Protocol::newInstanceToJson(MessageTypeEnum::ADMIN_USER_ONLINE, '', $user));
            }
        };
    }

    private function onMessageEvent()
    {
        return function (Server $server, Frame $frame) {

            $message = Protocol::parse($frame->data);
            switch ($message->getType()) {

                case MessageTypeEnum::CREATED_ROOM:

                    // 如果已经建立过了，那么就回应
                    if ($this->filesTable->exist($frame->fd)) {

                        $server->push($frame->fd, Protocol::newInstanceToJson(MessageTypeEnum::CREATED_ROOM, '你已经创建了房间，请勿重复'));
                        return;
                    }

                    // 建立房间
                    // 1. 生成房间链接
                    $uri = Str::random();

                    $user = User::newInstance(['id' => $frame->fd, 'shareLink' => $uri])->toArray();
                    $file = UserHasFile::newInstance(array_merge(['userId' => $frame->fd], $message->getData()))->toArray();

                    $this->usersTable->set($frame->fd, $user);
                    $this->filesTable->set($uri, $file);

                    // 推送给后台用户上线
                    $user['file'] = $file;
                    foreach ($this->adminsTable as $fd => $val) {

                        $server->push($fd, Protocol::newInstanceToJson(MessageTypeEnum::CREATED_ROOM, '', $user));
                    }
                    break;

                case MessageTypeEnum::ADMIN_CLOSE_CONNECT:

                    $fd = $message->getData()['fd'] ?? '';
                    if ($this->usersTable->exists($fd)) {

                        $server->disconnect($fd, RFC6455::CLOSE_CODE, Protocol::newInstanceToJson(MessageTypeEnum::CLOSE, "后台主动关闭"));
                    }
                    break;

                // 后台的请求
                case MessageTypeEnum::ADMIN_EVENT_INIT_DATA:

                    // 创建后台用户
                    if ($this->adminsTable->count() === $this->adminMaxCount) {

                        $server->disconnect($frame->fd);
                        return;
                    }

                    // 把管理员从用户表删除
                    if ($this->usersTable->exists($frame->fd)) {
                        $this->usersTable->del($frame->fd);
                    }

                    $users = [];
                    foreach ($this->usersTable as $user) {

                        if (! empty($user['shareLink'])) {

                            $user['file'] = $this->filesTable->get($user['shareLink']) ?: (new UserHasFile())->toArray();
                        } else {
                            $user['file'] = (new UserHasFile())->toArray();
                        }
                        $users[] = $user;
                    }
                    $this->adminsTable->set($frame->fd, Admin::newInstance(['id' => $frame->fd])->toArray());
                    $server->push($frame->fd, Protocol::newInstanceToJson(MessageTypeEnum::ADMIN_EVENT_INIT_DATA, 'success', $users));
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

            if ($this->adminsTable->exists($fd)) {

                $this->adminsTable->del($fd);
            }

            // 清除内存表
            if ($this->usersTable->exists($fd)) {

                $user = User::newInstance($this->usersTable->get($fd));

                $this->usersTable->del($user->getId());
                if (! is_null($user->getShareLink())) {

                    $this->filesTable->del($user->getShareLink());
                }

                // 推送给后台用户下线
                foreach ($this->adminsTable as $adminFd => $val) {

                    $server->push($adminFd, Protocol::newInstanceToJson(MessageTypeEnum::ADMIN_USER_OFFLINE, '', compact('fd')));
                }
            }
        };
    }
}