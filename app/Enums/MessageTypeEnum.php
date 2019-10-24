<?php

namespace App\Enums;


class MessageTypeEnum
{
    const COMMON = 1;
    const GET_FILE_INFO = 2;
    const GET_FILE_DATA = 3;
    const CREATED_ROOM = 4;

    const PUT_FILE_DATA = 5;

    const CLOSE = 99;

    const ADMIN_EVENT_INIT_DATA = 999;
    const ADMIN_CLOSE_CONNECT = 1000;
    const ADMIN_USER_UPDATED = 1001;
    const ADMIN_USER_ONLINE = 1002;
    const ADMIN_USER_OFFLINE = 1003;
}