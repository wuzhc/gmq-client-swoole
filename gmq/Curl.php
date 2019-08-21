<?php

namespace gmq;


class Curl
{
    protected $ch;

    public function __construct()
    {
        $this->ch = curl_init();
        $this->setDefaultUA();
        $this->setDefaultHeader();
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
    }

    public function get($url)
    {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        $res = curl_exec($this->ch);

        $errno = curl_errno($this->ch);
        $errmsg = curl_error($this->ch);

        if ($errmsg) {
            return [$res, Errors::newErr($errmsg, $errno)];
        } else {
            return [$res, null];
        }
    }

    public function setDefaultUA()
    {
        $ua = 'gmq/ v111';
        $cv = curl_version();
        $ua .= ' curl/' . $cv['version'];
        curl_setopt($this->ch, CURLOPT_USERAGENT, $ua);
    }

    public function setDefaultHeader()
    {
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
    }
}