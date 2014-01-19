<?php

namespace LinguaLeo\MySQL;

use LinguaLeo\MySQL\Exception\MySQLException;
use \PDO;

class Connection extends PDO
{
    private $nestedTransactionCount = 0;

    public function __construct($host, $user, $passwd, $options = [])
    {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

        parent::__construct('mysql:host='.$host, $user, $passwd, $options);
    }

    public function ping()
    {
        return $this->query('SELECT 1');
    }

    public function beginTransaction()
    {
        $ok = true;
        if (0 === $this->nestedTransactionCount) {
            $ok = parent::beginTransaction();
        }
        $this->nestedTransactionCount++;
        return $ok;
    }

    public function commit()
    {
        $ok = true;
        if (1 === $this->nestedTransactionCount) {
            $ok = parent::commit();
        } elseif ($this->nestedTransactionCount < 1) {
            throw new MySQLException('You cannot make commit without begin', 10254);
        }
        $this->nestedTransactionCount--;
        return $ok;
    }

    public function rollBack(\Exception $e = null)
    {
        $ok = true;
        if (1 === $this->nestedTransactionCount) {
            $ok = parent::rollBack();
        } else {
            throw new MySQLException('Nested transaction is rolled back', 10255, $e);
        }
        $this->nestedTransactionCount--;
        return $ok;
    }
}
