<?php

namespace gmq;


use Swoole\Client;
use Swoole\Process;
use Swoole\Timer;

class Consumer
{
    protected $handlers = [];
    protected $weight;
    protected $topic;

    /** @var  Event */
    protected $event;

    public function __construct($topic)
    {
        $this->topic = $topic;
        $this->event = new Event();
    }

    public function onMessage($func)
    {
        $this->event->onMessage = $func;
    }

    public function onError($func)
    {
        $this->event->onError = $func;
    }

    public function onJob($func)
    {
        $this->event->onJob = $func;
    }

    public function getNodes($url = '')
    {
        $url or $url = 'http://127.0.0.1:9595/getNodes';

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
        if ($err = $this->isInstallCallbackFunc()) {
            echo $err->msg;
            exit(-1);
        }

        $nodes = $this->getNodes($url);
        foreach ($nodes as $node) {
            $this->connectToSpecifiedNode($node);
        }

        $this->installTimer(3000);
    }

    public function connectToNode($tcpAddr, $weight = 1)
    {
        $node = [
            'weight'   => $weight,
            'tcp_addr' => $tcpAddr,
        ];

        $this->connectToSpecifiedNode($node);
    }

    protected function connectToSpecifiedNode($node)
    {
        $process = new Process(function (Process $worker) use ($node) {
            $addr = $node['tcp_addr'];
            swoole_set_process_name(sprintf('gmq-client:%s', $addr));
            list($host, $port) = explode(':', $addr, 2);

            // 异步客户端
            $client = new Client(SWOOLE_SOCK_TCP | SWOOLE_ASYNC);
            $client->count = 0;

            $client->set([
                'open_length_check'     => 1,
                'package_length_type'   => 'n',
                'package_length_offset' => 2,
                'package_body_offset'   => 4,
                'package_max_length'    => 2000000,
            ]);

            $client->on("connect", function (Client $cli) {
                $cli->send(Protocol::pack(Pkg::newPop($this->topic)));
            });

            $client->on("receive", function (Client $cli, $data) use ($node) {
                $res = Protocol::unpack($data);
                $res->from = $node['tcp_addr'];
                $ctx = Context::newObj($res, $cli);

                switch ($res->type) {
                    case Response::RESP_JOB:
                        call_user_func($this->event->onJob, $ctx);
                        break;
                    case Response::RESP_ERR:
                        call_user_func($this->event->onError, $ctx);
                        break;
                    case Response::RESP_MSG:
                        call_user_func($this->event->onMessage, $ctx);
                        break;
                    default:
                        echo sprintf("[from %s] [unknown] %s\n", $node['tcp_addr'], $res->body);
                }

                // 下一个消息
                $cli->send(Protocol::pack(Pkg::newPop($this->topic)));
            });

            $client->on("error", function (Client $cli) {
                echo "Connection close\n";
            });

            $client->on("close", function (Client $cli) {
                echo "Connection close\n";
            });

            $res = $client->connect($host, $port, 30);
            if (!$res) {
                echo 'start process failed' . PHP_EOL;
            } else {
                echo sprintf("connect to %s success\n", $host . ':' . $port);
            }

        }, false, false);

        $pid = $process->start();
        $h = ConsumerHandler::newObj($pid, $node['tcp_addr'], $node['weight']);
        $this->handlers[$node['tcp_addr']] = $h;

        return null;
    }

    public function processWait()
    {
        // 不会阻塞等待
        // 子进程退出时,会触发回调方法
        // 终端中止,会触发回调方法,但不会有输出
        Process::signal(SIGCHLD, function ($sig) {
            while ($ret = Process::wait(false)) {
                echo "PID={$ret['pid']}\n";
            }
        });
    }

    protected function isInstallCallbackFunc()
    {
        if (!is_callable($this->event->onJob)) {
            return Errors::newErr('onJob is not a function');
        }
        if (!is_callable($this->event->onMessage)) {
            return Errors::newErr('onMessage is not a function');
        }
        if (!is_callable($this->event->onError)) {
            return Errors::newErr('onError is not a function');
        }
    }

    protected function installTimer($ms = 3000)
    {
        Timer::tick($ms, function ($timerID, ...$params) {
            $nodes = $this->getNodes();
            /** @var ConsumerHandler $h */
            foreach ($this->handlers as $h) {
                $isOffline = true;
                foreach ($nodes as $k => $node) {
                    if ($h->addr == $node['tcp_addr']) {
                        $isOffline = false;
                        unset($nodes[$k]);
                        break;
                    }
                }
                // 节点下线处理
                if (true === $isOffline) {
                    Process::kill($h->pid, SIGINT);
                }
            }

            // 添加新节点
            foreach ($nodes as $node) {
                $this->connectToSpecifiedNode($node);
            }
        });
    }
}

class ConsumerHandler
{
    public $addr;
    public $weight;
    public $pid;

    public static function newObj($pid, $addr, $weight)
    {
        $obj = new self();
        $obj->addr = $addr;
        $obj->pid = $pid;
        $obj->weight = $weight;
        return $obj;
    }
}

class Event
{
    public $onMessage;
    public $onError;
    public $onJob;
}