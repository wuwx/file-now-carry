<?php


namespace App;


use App\Enums\MessageTypeEnum;
use App\Enums\RFC6455;
use App\Messages\Protocol;
use App\Models\Admin;
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
    protected $usersTable;
    protected $filesTable;
    protected $adminsTable;

    protected $atomic;
    protected $server;

    protected $max;
    protected $adminMaxCount = 9;

    protected $port;
    protected $publicDomain;


    public function __construct($max = 100, $port = 9999, $publicDomain = '127.0.0.1')
    {
        $this->port = $port;
        $this->publicDomain = $publicDomain;
        $this->max = $max;

        $this->server = new SocketServer('0.0.0.0', $this->port);

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
                    $shareLink = Str::random();

                    $user = User::newInstance(['id' => $frame->fd, 'shareLink' => $shareLink])->toArray();
                    $file = UserHasFile::newInstance(array_merge(['userId' => $frame->fd, 'shareLink' => $shareLink, 'linkUserId' => null], $message->getData()))->toArray();

                    $this->usersTable->set($frame->fd, $user);
                    $this->filesTable->set($shareLink, $file);

                    // 告诉前台生成的连接
                    $server->push($frame->fd, Protocol::newInstanceToJson(MessageTypeEnum::CREATED_ROOM, '', compact('shareLink')));

                    // 推送给后台用户上线
                    $user['file'] = $file;
                    foreach ($this->adminsTable as $fd => $val) {

                        $server->push($fd, Protocol::newInstanceToJson(MessageTypeEnum::CREATED_ROOM, '', $user));
                    }
                    break;


                case MessageTypeEnum::GET_FILE_INFO:
                    $shareLink = $message->getData()['shareLink'] ?? '';

                    // 要看这个链接是否被别人占用
                    $fileInfo = UserHasFile::newInstance($this->filesTable->get($shareLink));

                    if (is_null($fileInfo->getFileSize())) {

                        $server->push($frame->fd, Protocol::newInstanceToJson(MessageTypeEnum::GET_FILE_INFO, '无效的文件信息', ['code' => 404]));
                    }
//                    elseif ($fileInfo->getLinkUserId()) {
//
//                        $server->push($frame->fd, Protocol::newInstanceToJson(MessageTypeEnum::GET_FILE_INFO, '连接已被占用', ['code' => 403]));
//                    }
                    else {

                        $linkInfo = ['linkUserId' => $frame->fd];
                        $user = User::newInstance($this->usersTable->get($fileInfo->getUserId()))->toArray();
                        $user['file'] = array_merge($fileInfo->toArray(), $linkInfo);

                        // 我自己连接上, 并且标记我为不是分享用户
                        $linkData = ['shareLink' => $fileInfo->getShareLink(), 'isShare' => 0];
                        $this->usersTable->set($frame->fd, $linkData);
                        // 更新到内存表有人进行了连接
                        $this->filesTable->set($fileInfo->getShareLink(), $linkInfo);

                        $linkUser = array_merge(['id' => $frame->fd], $linkData);

                        // 推送给后台更新
                        foreach ($this->adminsTable as $adminFd => $row) {

                            $server->push($adminFd, Protocol::newInstanceToJson(MessageTypeEnum::ADMIN_USER_UPDATED, '', $user));
                            $server->push($adminFd, Protocol::newInstanceToJson(MessageTypeEnum::ADMIN_USER_UPDATED, '', $linkUser));
                        }

                        // 返回给连接人
                        $server->push($frame->fd, Protocol::newInstanceToJson(MessageTypeEnum::GET_FILE_INFO, '', array_merge(['code' => 200], $fileInfo->toArray())));

                        // 通知文件上传人，有人连接了
                        $server->push($fileInfo->getUserId(), Protocol::newInstanceToJson(MessageTypeEnum::GET_FILE_INFO, '', $linkInfo));
                    }

                    break;
                case MessageTypeEnum::GET_FILE_DATA:

                    $index = $message->getData()['index'] ?? 0;
                    $size = $message->getData()['size'] ?? 1024;

                    // 得到 client 要取第几块的数据
                    $self = User::newInstance($this->usersTable->get($frame->fd));
                    $shareFile = UserHasFile::newInstance($this->filesTable->get($self->getShareLink()));
                    $shareUser = User::newInstance($this->usersTable->get($shareFile->getUserId()));

                    $server->push($shareUser->getId(), Protocol::newInstanceToJson(MessageTypeEnum::GET_FILE_DATA, '', compact('index', 'size')));
                    break;

                case MessageTypeEnum::PUT_FILE_DATA:
                    // 得到 client 要取第几块的数据
                    $shareUser = User::newInstance($this->usersTable->get($frame->fd));
                    $shareFile = UserHasFile::newInstance($this->filesTable->get($shareUser->getShareLink()));
                    $user = User::newInstance($this->usersTable->get($shareFile->getLinkUserId()));

                    $server->push($user->getId(), Protocol::newInstanceToJson(MessageTypeEnum::PUT_FILE_DATA, '', $message->getData()));
                    break;

                case MessageTypeEnum::ADMIN_CLOSE_CONNECT:

                    $fd = $message->getData()['fd'] ?? '';
                    if ($this->usersTable->exists($fd)) {

                        $server->disconnect($fd, RFC6455::CLOSE_CODE, Protocol::newInstanceToJson(MessageTypeEnum::COMMON, "后台主动关闭"));
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

                        $user['file'] = $this->filesTable->get($user['shareLink']) ?: (new UserHasFile())->toArray();
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

            $file = trim($request->server['request_uri'], '/');

            $response->end('hello');
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

                    // 如果是分享者，那么直接删除
                    if ($user->getIsShare()) {

                        $this->filesTable->del($user->getShareLink());
                    } else {

                        // 否者移除掉自己即可
                        $this->filesTable->set($user->getShareLink(), ['linkUserId' => $user->getShareLink()]);
                        // 分享者发生了变化
                        $shareFile = UserHasFile::newInstance($this->filesTable->get($user->getShareLink()));
                        $shareUser = User::newInstance($this->usersTable->get($shareFile->getUserId()))->toArray();
                        $shareUser['file'] = $shareFile->toArray();

                        // 推送给后台用户下线
                        foreach ($this->adminsTable as $adminFd => $val) {

                            $server->push($adminFd, Protocol::newInstanceToJson(MessageTypeEnum::ADMIN_USER_UPDATED, '', $shareUser));
                        }
                    }
                }

                // 推送给后台用户下线
                foreach ($this->adminsTable as $adminFd => $val) {

                    $server->push($adminFd, Protocol::newInstanceToJson(MessageTypeEnum::ADMIN_USER_OFFLINE, '', compact('fd')));
                }
            }
        };
    }
}