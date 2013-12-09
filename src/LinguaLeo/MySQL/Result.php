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

    /**
     * {@inheritdoc}
     */
    public function keyValue()
    {
        $result = [];
        while ($row = $this->stmt->fetch(\PDO::FETCH_NUM)) {
            $result[$row[0]] = $row[1];
        }
        $this->stmt->closeCursor();
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function many()
    {
        $rows = $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->stmt->closeCursor();
        return $rows;
    }

    /**
     * {@inheritdoc}
     */
    public function one()
    {
        $row = $this->stmt->fetch(\PDO::FETCH_ASSOC);
        $this->stmt->closeCursor();
        return $row;
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
        $this->stmt->closeCursor();
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
        $column = $this->stmt->fetchColumn($number);
        $this->stmt->closeCursor();
        return $column;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $count = $this->stmt->rowCount();
        $this->stmt->closeCursor();
        return $count;
    }
}