<?php

namespace LinguaLeo\MySQL;

class Pool
{
    private $config;
    private $pool;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $dbname
     * @param bool $force
     * @return Connection
     */
    public function connect($dbname, $force = false)
    {
        $host = $this->config->getHost($dbname);

        if (empty($this->pool[$host]) || $force) {
            $this->pool[$host] = $this->create($host);
        }

        return $this->pool[$host];
    }


    protected function create($host)
    {
        return new Connection($host, $this->config->getUser(), $this->config->getPasswd());
    }
}
