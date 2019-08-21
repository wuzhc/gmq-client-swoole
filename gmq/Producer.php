<?php

namespace gmq;

use Swoole\Client;


class Producer
{
    protected $handlers = [];
    protected $weight;

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

    protected function connectToSpecifiedNode($node)
    {
        $producer = new Client(SWOOLE_SOCK_TCP);
        $producer->set([
            'open_length_check'     => 1,
            'package_length_type'   => 'n',
            'package_length_offset' => 2,
            'package_body_offset'   => 4,
            'package_max_length'    => 2000000,
        ]);

        list($host, $port) = explode(':', $node['tcp_addr'], 2);
        if (!$producer->connect($host, $port)) {
            return Errors::newErr(sprintf('connect %s failed', $node['tcp_addr']), 1);
        }

        $h = new Handler();
        $h->addr = $node['tcp_addr'];
        $h->weight = $node['weight'];
        $h->conn = $producer;

        $this->handlers[$node['tcp_addr']] = $h;
        $this->weight += $node['weight'];

        return null;
    }

    public function publish($job)
    {
        $n = rand(1, $this->weight);
        $w = 0;

        /** @var Handler $handler */
        foreach ($this->handlers as $addr => $handler) {
            $nw = $w + $handler->weight;
            if ($n >= $w && $n <= $nw) {
                $res = $handler->conn->send(Protocol::pack(Pkg::newPush($job)));
                if (!$res) {
                    $reJob = new ReJob();
                    $reJob->job = $job;
                    $reJob->oldAddr = $addr;
                    $reJob->attempts = 1;
                    return $this->rePublish($reJob);
                } else {
                    return Protocol::unpack($handler->conn->recv());
                }
            }
            $w += $nw;
        }

        return Response::newErr('not handlers');
    }

    /**
     * @param reJob $reJob
     * @return Response
     */
    public function rePublish($reJob)
    {
        /** @var Handler $handler */
        foreach ($this->handlers as $addr => $handler) {
            if ($reJob->attempts > 5) {
                return Response::newErr(sprintf('job.id %s publish failed, %s', $reJob->job->id, $reJob->attempts));
            }

            if ($addr == $reJob->oldAddr) {
                continue;
            }

            $res = $handler->conn->send(Protocol::pack(Pkg::newPush($reJob->job)));
            if ($res) {
                return Protocol::unpack($handler->conn->recv());
            }

            $reJob->attempts++;
        }

        return Response::newErr(sprintf('job.id %s publish failed, %s', $reJob->job->id, $reJob->attempts));
    }
}

class Handler
{
    public $addr;
    public $weight;
    /** @var Client */
    public $conn;
}

class ReJob
{
    public $job;
    public $oldAddr;
    public $attempts;
}