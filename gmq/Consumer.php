<?php
/**
 * Created by PhpStorm.
 * User: wuzhc
 * Date: 19-8-21
 * Time: 上午10:15
 */

namespace gmq;


class Consumer
{
    protected $client;

    public function __construct()
    {
        $client = new Swoole\Client(SWOOLE_SOCK_TCP | SWOOLE_ASYNC);
        $client->set([
            'open_length_check'     => 1,
            'package_length_type'   => 'n',
            'package_length_offset' => 2,       //第N个字节是包长度的值
            'package_body_offset'   => 8,       //第几个字节开始计算长度
            'package_max_length'    => 2000000,  //协议最大长度
        ]);
        $client->on("connect", function (swoole_client $cli) {
            $cli->send(pack('a4', 'v111') . pack('n', strlen('pop')) . pack('n', strlen('wuzhc')) . pack('a3', 'pop')
                . pack('a5', 'wuzhc'));
        });

        $client->on("receive", function (swoole_client $cli, $data) {
            // print_r(unpack('nrespType', substr($data, 0, 2)));
            // print_r(unpack('Nlen', substr($data, 2, 6)));
            // print_r(unpack('ndataLen', substr($data, 6, 8)));
            // print_r(unpack('a*data', substr($data, 8)));

            print_r(unpack('nrespType', substr($data, 0, 2)));
            print_r(unpack('ndataLen', substr($data, 2, 4)));
            print_r(unpack('Nlen', substr($data, 4, 8)));
            print_r(unpack('a*data', substr($data, 8)));

            echo '------------' . PHP_EOL;
        });
        $client->on("error", function (swoole_client $cli) {
            echo "error\n";
        });
        $client->on("close", function (swoole_client $cli) {
            echo "Connection close\n";
        });
        $client->connect('127.0.0.1', 9999);
    }
}