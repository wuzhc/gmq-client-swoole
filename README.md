> 这是一个php版本的gmq客户端,基于swoole开发

## 生产者
该版本的生成者是同步模式,发布消息后会等待响应,然后进行下个消息发布,当发布完毕后,客户端主动关闭与服务端的连接
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