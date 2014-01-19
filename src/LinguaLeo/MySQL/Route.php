<?php

namespace LinguaLeo\MySQL;

class Route
{
    private $dbName;
    private $tableName;

    public function __construct($dbName, $tableName)
    {
        $this->dbName = $dbName;
        $this->tableName = $tableName;
    }

    public function getDbName()
    {
        return $this->dbName;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function __toString()
    {
        return $this->dbName.'.'.$this->tableName;
    }
}