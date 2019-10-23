function Application()
{
    this.ws = new WebSocket('ws://127.0.0.1:999');

    Application.MessageTypeEnum = {
        COMMON: 1,
        FILE_PRE_UPLOAD: 1,
        FILE_UPLOADING: 2,
        FILE_UPLOADED: 3,
        CREATED_ROOM: 4,
        CLOSE: 0,


        ADMIN_EVENT_INIT_DATA: 999,
        ADMIN_CLOSE_CONNECT: 1000,
        ADMIN_EVENT_ADD_LINK: 1001,
        ADMIN_USER_ONLINE: 1002,
        ADMIN_USER_OFFLINE: 1003,
    };

    this.setOnMessage = function (event) {

        this.ws.onmessage = event;
    };
    this.setOnOpen = function (event) {

        this.ws.onopen = event;
    };
    this.setOnClose = function (event) {

        this.ws.onclose = event;
    };
    /**
     * 通信协议类
     * @param type
     * @param msg
     * @param data
     * @constructor
     */
    this.newProtocol = function (type, msg, data)
    {
        let obj = {
            type: type,
            msg: msg,
            data: data
        };

        obj.parse = function (text) {

            try {

                let json = JSON.parse(text);
                this.type = json.type;
                this.msg = json.msg;
                this.data = json.data;

            } catch (e) {

                this.type = MessageTypeEnum.COMMON;
                this.msg = '';
                this.data = {};
            }

            return this;
        };

        obj.toJson = function () {

            let json = {
                type: this.type,
                msg: this.msg,
                data: this.data
            };

            return JSON.stringify(json);
        };

        return obj;
    }
}