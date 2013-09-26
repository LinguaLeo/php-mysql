<?php

namespace LinguaLeo\MySQL\HS;

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

    protected function getNewCriteria()
    {
        return new Criteria($this->schemaName, $this->tableName);
    }

    protected function selectOne($criteria)
    {
        $response = $this->query->select($criteria);

        return $response;
    }

    protected function selectValue($criteria, $column)
    {
        $row = $this->selectOne($criteria);

        if (isset($row[$column])) {
            return $row[$column];
        }

        return false;
    }
}
