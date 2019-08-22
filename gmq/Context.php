<?php
/**
 * Created by PhpStorm.
 * User: wuzhc
 * Date: 19-8-22
 * Time: 下午3:27
 */

namespace gmq;


class Context
{
    public $from;
    public $body;
    public $handler;

    /**
     * @param $resp
     * @param $handler
     * @return Context
     */
    public static function newObj($resp, $handler)
    {
        $obj = new self();
        $obj->from = $resp->from;
        $obj->body = $resp->body;
        $obj->handler = $handler;
        return $obj;
    }
}