<?php

use gmq\Protocol;
use gmq\Pkg;
use gmq\Job;
use Swoole\Client;

include 'autoload.php';

$producer = new Client(SWOOLE_SOCK_TCP);
$producer->set([
    'open_length_check'     => 1,
    'package_length_type'   => 'n',
    'package_length_offset' => 2,
    'package_body_offset'   => 4,
    'package_max_length'    => 2000000,
]);
$producer->connect('127.0.0.1', 9503, 30, -1);

// 模拟生成100个消息
for ($i = 0; $i < 100; $i++) {
    $job = new Job();
    $job->id = uniqid(time());
    $job->topic = 'topic_007';
    $job->body = 'hello gmq';
    $job->delay = 10;

    $producer->send(Protocol::pack(Pkg::newPush($job)));
    print_r(Protocol::unpack($producer->recv()));
}

