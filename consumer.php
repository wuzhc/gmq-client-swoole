<?php

use gmq\Consumer;
use gmq\Context;

include 'autoload.php';

$c = new Consumer('topic_007');
$c->onJob(function (Context $ctx) {
    echo sprintf("[from %s] %s\n", $ctx->from, $ctx->body);
});
$c->onMessage(function (Context $ctx) {
    echo sprintf("[from %s] %s\n", $ctx->from, $ctx->body);
});
$c->onError(function (Context $ctx) {
    echo sprintf("[from %s] %s\n", $ctx->from, $ctx->body);
});

$url = 'http://127.0.0.1:9595/getNodes';
$c->connectToRegister($url);
$c->processWait();
