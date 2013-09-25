<?php

namespace LinguaLeo\MySQL\HS;

use HSPHP\WriteSocket;

class Pool extends \LinguaLeo\MySQL\Pool
{
    private $port;

    public function __construct(Configuration $config, $port)
    {
        parent::__construct($config);
        $this->port = $port;
    }

    protected function create($host)
    {
        $socket = new WriteSocket();
        $socket->connect($host, $this->port);
        return $socket;
    }
}