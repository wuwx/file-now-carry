<?php

namespace App\Enums;


class MessageTypeEnum
{
    const COMMON = 1;

    const CREATED_ROOM = 4;
    const CLOSE = 0;

    const ADMIN_EVENT_INIT_DATA = 999;
    const ADMIN_CLOSE_CONNECT = 1000;
    const ADMIN_EVENT_ADD_LINK = 1001;
    const ADMIN_USER_ONLINE = 1002;
    const ADMIN_USER_OFFLINE = 1003;
}