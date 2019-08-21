<?php

namespace gmq;


class Protocol
{
    public static function pack(Pkg $pkg)
    {
        $version = pack('a4', 'v111');
        $cmdLen = pack('n', $pkg->cmdLen);
        $bodyLen = pack('n', $pkg->bodyLen);
        $cmd = pack('a*', $pkg->cmd);
        $body = pack('a*', $pkg->body);

        return $version . $cmdLen . $bodyLen . $cmd . $body;
    }

    public static function unpack($str)
    {
        $type = unpack('n', substr($str, 0, 2));
        $bodyLen = unpack('n', substr($str, 2, 4));
        $body = unpack('a*', substr($str, 4));

        $resp = new Response();
        $resp->type = $type[1];
        $resp->bodyLen = $bodyLen[1];
        $resp->body = $body[1];

        return $resp;
    }
}