<?php

use gmq\Pkg;
use gmq\Protocol;

include 'autoload.php';

$client = new Swoole\Client(SWOOLE_SOCK_TCP | SWOOLE_ASYNC);
$client->set([
    'open_length_check'     => 1,
    'package_length_type'   => 'n',
    'package_length_offset' => 2,
    'package_body_offset'   => 4,
    'package_max_length'    => 2000000,
]);

$client->on("connect", function (swoole_client $cli) {
    $cli->send(Protocol::pack(Pkg::newPop('topic_007')));
});

$client->on("receive", function (swoole_client $cli, $data) {
    $res = Protocol::unpack($data);
    print_r($res);

    // 当一个消息消费完成后,再获取一个消息
    $cli->send(Protocol::pack(Pkg::newPop('topic_007')));
});

$client->on("error", function (swoole_client $cli) {
    echo "error\n";
});

$client->on("close", function (swoole_client $cli) {
    echo "Connection close\n";
});

$client->connect('127.0.0.1', 9503);
