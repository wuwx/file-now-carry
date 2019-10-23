<?php

namespace App\Enums;


class MessageTypeEnum
{
    const COMMON = 1;

    const CREATED_ROOM = 4;
    const CLOSE = 0;

    const ADMIN_EVENT_INIT_DATA = 999;
    const ADMIN_EVENT_ADD_USER = 1000;
    const ADMIN_EVENT_ADD_LINK = 1001;
}