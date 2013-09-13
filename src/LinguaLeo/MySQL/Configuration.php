<?php

namespace LinguaLeo\MySQL;

use LinguaLeo\MySQL\Exception\PoolException;

class Configuration
{
    protected $map;
    protected $user;
    protected $passwd;

    /**
     * Instantiate the configuration
     *
     * @param array $map The databases mapping on hosts
     * @param string $user
     * @param string $passwd
     */
    public function __construct($map, $user, $passwd)
    {
        $this->map = $map;
        $this->user = $user;
        $this->passwd = $passwd;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getPasswd()
    {
        return $this->passwd;
    }

    /**
     * Returns the host for the database
     *
     * @param string $dbname
     * @return string
     * @throws PoolException
     */
    public function getHost($dbname)
    {
        if (empty($this->map[$dbname])) {
            throw new PoolException(sprintf('Host is not defined for %s database', $dbname));
        }

        return $this->map[$dbname];
    }
}
