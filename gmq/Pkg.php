<?php

namespace gmq;


class Pkg
{
    public $version;
    public $cmdLen;
    public $bodyLen;
    public $cmd;
    public $body;

    public static function newPush(Job $job)
    {
        if ($err = $job->validate()) {
            return $err;
        }

        $p = new self();
        $p->version = 'v111';
        $p->cmd = 'push';
        $p->cmdLen = strlen('push');
        $p->body = $job->string();
        $p->bodyLen = strlen($job->string());

        return $p;
    }

    public static function newPop($topic)
    {
        if (empty($topic)) {
            return Errors::newErr('topic is empty');
        }

        $p = new self();
        $p->version = 'v111';
        $p->cmd = 'pop';
        $p->cmdLen = strlen('pop');
        $p->body = $topic;
        $p->bodyLen = strlen($topic);

        return $p;
    }

    public static function newAck($jobId)
    {
        if (empty($jobId)) {
            return Errors::newErr('job.id is empty');
        }

        $p = new self();
        $p->version = 'v111';
        $p->cmd = 'ack';
        $p->cmdLen = strlen('ack');
        $p->body = $jobId;
        $p->bodyLen = strlen($jobId);

        return $p;
    }
}