<?php

namespace LinguaLeo\MySQL;

use LinguaLeo\DataQuery\ResultInterface;

class Result implements ResultInterface
{
    /**
     * @var \PDOStatement
     */
    private $stmt;

    public function __construct(\PDOStatement $stmt)
    {
        $this->stmt = $stmt;
    }

    public function __destruct()
    {
        $this->free();
    }

    /**
     * {@inheritdoc}
     */
    public function keyValue()
    {
        $result = [];
        while ($row = $this->stmt->fetch(\PDO::FETCH_NUM)) {
            $result[$row[0]] = $row[1];
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function many()
    {
        return $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function one()
    {
        return $this->stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function table()
    {
        $table = [];
        while ($row = $this->stmt->fetch(\PDO::FETCH_ASSOC)) {
            foreach ($row as $column => $value) {
                $table[$column][] = $value;
            }
        }
        return $table;
    }

    /**
     * {@inheritdoc}
     */
    public function value($name)
    {
        $row = $this->one();
        if (isset($row[$name])) {
            return $row[$name];
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function column($number)
    {
        return $this->stmt->fetchColumn($number);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->stmt->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function free()
    {
        if ($this->stmt) {
            $this->stmt->closeCursor();
            $this->stmt = null;
            return true;
        }
        return false;
    }
}