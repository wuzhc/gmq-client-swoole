<?php
/**
 * Created by PhpStorm.
 * User: wuzhc
 * Date: 19-8-21
 * Time: 下午4:48
 */

namespace gmq;


use Swoole\Process;
use Swoole\Timer;

abstract class Base
{
    protected $handlers = [];

    abstract protected function connectToSpecifiedNode($node);

    public function getNodes($url)
    {
        $curl = new Curl();
        list($resp, $err) = $curl->get($url);
        if ($err) {
            echo $err->msg;
            exit(-1);
        }

        $data = json_decode($resp, true);
        if ((int)$data['code'] !== 0 || empty($data['data'])) {
            echo 'get nodes failed';
            exit(-1);
        }

        return $data['data']['nodes'];
    }

    public function connectToRegister($url)
    {
        $nodes = $this->getNodes($url);
        foreach ($nodes as $node) {
            $err = $this->connectToSpecifiedNode($node);
            if ($err) {
                echo $err->msg;
                exit(-1);
            }
        }
        //
        // Timer::tick(3000, function ($timerID, ...$params) {
        //     echo "timeout \n";
        // });
    }

    public function connectToNode($tcpAddr, $weight = 1)
    {
        $node = [
            'weight'   => $weight,
            'tcp_addr' => $tcpAddr,
        ];

        $err = $this->connectToSpecifiedNode($node);
        if ($err) {
            echo $err->msg;
            exit(-1);
        }
    }
}