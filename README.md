> 这是一个php版本的gmq客户端,基于swoole开发

## 生产者
生成者发布消息是同步模式,支持消息发布失败重试功能(发布给一个失败后,会尝试发布给其他节点)
producer.php
```php
<?php

use gmq\Producer;
use gmq\Job;
use gmq\Response;

include 'autoload.php';

$url = 'http://127.0.0.1:9595/getNodes';
$p = new Producer();

// 连接到注册中心
$p->connectToRegister($url);

// 或者直接连接到指定节点
// $p->connectToNode('127.0.0.1:9501');
// $p->connectToNode('127.0.0.1:9502');

// 发布100个消息
for ($i = 0; $i < 100; $i++) {
    $job = new Job();
    $job->id = uniqid(time());
    $job->topic = 'topic_007';
    $job->body = 'hello gmq';
    $job->delay = rand(0, 300);

    $resp = $p->publish($job);
    switch ($resp->type) {
        case Response::RESP_ERR:
            // do something
            echo '[failed] ' . $resp->body . PHP_EOL;
            break;
        case Response::RESP_MSG:
            // do something
            echo '[success] ' . $resp->body . PHP_EOL;
            break;
        default:
            echo '[unknown] ' . $resp->body . PHP_EOL;
    }
}
```

## 消费者
消费使用swoole的异步客户端,用户可以自定义消费函数
```php
<?php

use gmq\Consumer;
use gmq\Context;

include 'autoload.php';

// 实例化消费者时需要指定主题
$c = new Consumer('topic_007');
// 消息回调处理
$c->onJob(function (Context $ctx) {
    echo sprintf("[from %s] %s\n", $ctx->from, $ctx->body);
});
// 结果回调处理
$c->onMessage(function (Context $ctx) {
    echo sprintf("[from %s] %s\n", $ctx->from, $ctx->body);
});
// 错误回调处理
$c->onError(function (Context $ctx) {
    echo sprintf("[from %s] %s\n", $ctx->from, $ctx->body);
});

$url = 'http://127.0.0.1:9595/getNodes';
$c->connectToRegister($url);
// $c->connectToNode('127.0.0.1:9503');
$c->processWait();
```