<?php

namespace gmq;


class Job
{
    public $id;
    public $topic;
    public $body;
    public $TTR;
    public $delay;

    public function validate()
    {
        if (empty($this->id)) {
            return Errors::newErr('job.id is empty');
        }
        if (empty($this->topic)) {
            return Errors::newErr('job.topic is empty');
        }
        if (empty($this->body)) {
            return Errors::newErr('job.body is empty');
        }
    }

    public function string()
    {
        return json_encode([
            'id'    => $this->id,
            'topic' => $this->topic,
            'body'  => $this->body,
            'TTR'   => $this->TTR,
            'delay' => $this->delay
        ]);
    }
}