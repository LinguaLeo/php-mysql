<?php

namespace LinguaLeo\MySQL;

class Peer
{
    /**
     * @var Query
     */
    protected $query;

    /**
     * @var string
     */
    protected $schemaName;

    /**
     * @var string
     */
    protected $tableName;

    public function __construct($query, $schemaName, $tableName)
    {
        $this->query = $query;
        $this->schemaName = $schemaName;
        $this->tableName = $tableName;
    }

    protected function openTable()
    {
        return $this->query->table($this->tableName, $this->schemaName);
    }

    protected function fetchPair($stmt)
    {
        $result = [];

        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $result[$row[0]] = $row[1];
        }

        $stmt->closeCursor();

        return $result;
    }

    protected function fetchOne($stmt)
    {
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        return $row;
    }

    protected function fetchValue($stmt, $column)
    {
        $row = $this->fetchOne($stmt);

        if (isset($row[$column])) {
            return $row[$column];
        }

        return false;
    }

    protected function fetchTable($stmt)
    {
        $table = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            foreach ($row as $column => $value) {
                $table[$column][] = $value;
            }
        }

        $stmt->closeCursor();

        return $table;
    }
}
