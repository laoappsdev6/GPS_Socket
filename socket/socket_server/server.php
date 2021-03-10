<?php

set_time_limit(0);

require_once "../../services/services.php";
require_once "../socket_api/base.api.php";
class SocketServer
{

    private $host = "127.0.0.1";
    private $port = "20205";
    private $data_limit = 10240;
    private $socket;

    public function __construct()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, 0) or die(PrintJSON("","Connot create Socket Server",0));
        echo "Server is starting...";
        socket_bind($this->socket, $this->host, $this->port) or die (PrintJSON("","Cannot bind data to create socket",0));
        socket_listen($this->socket, 3)  or die (PrintJSON("","Cannot listen socket client to connect",0));
    }

    public function onMessage()
    {

        do {
            $accept = socket_accept($this->socket)  or die (PrintJSON("","Cannot accept connection to socket client",0));

            $msg = socket_read($accept, $this->data_limit) or die (PrintJSON("","Cannot read data from client",0));

            $api = new BaseAPI();
            $json = $api->checkCommand($msg);
            socket_write($accept, $json, strlen($json))  or die (PrintJSON("","Cannot send data to client",0));
        } while (true);

        $this->onClose();
    }
    public function onClose()
    {
        socket_close($this->socket);
    }
}
$server = new SocketServer();
$server->onMessage();
