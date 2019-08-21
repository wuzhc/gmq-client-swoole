<?php

namespace gmq;


class Response
{
    const RESP_JOB = 0;
    const RESP_ERR = 1;
    const RESP_MSG = 2;

    public $type;
    public $bodyLen;
    public $body;

    public static function newErr($err)
    {
        $resp = new self();
        $resp->type = self::RESP_ERR;
        $resp->body = $err;
        $resp->bodyLen = strlen($err);
        return $resp;
    }
}