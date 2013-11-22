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

    /**
     * @return string
     */
    protected function getLastInsertId()
    {
        return $this->query->getConnection($this->schemaName)->lastInsertId();
    }

    protected function getNewCriteria()
    {
        return new Criteria($this->schemaName, $this->tableName);
    }

    protected function selectKeyValue($criteria)
    {
        $stmt = $this->query->select($criteria);

        $result = [];

        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $result[$row[0]] = $row[1];
        }

        $stmt->closeCursor();

        return $result;
    }

    protected function selectOne($criteria)
    {
        $stmt = $this->query->select($criteria);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        return $row;
    }

    protected function selectValue($criteria, $column)
    {
        $row = $this->selectOne($criteria);

        if (isset($row[$column])) {
            return $row[$column];
        }

        return false;
    }

    protected function selectMany($criteria, $style = \PDO::FETCH_ASSOC)
    {
        $stmt = $this->query->select($criteria);

        $items = $stmt->fetchAll($style);

        $stmt->closeCursor();

        return $items;
    }

    protected function selectTable($criteria)
    {
        $stmt = $this->query->select($criteria);

        $table = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            foreach ($row as $column => $value) {
                $table[$column][] = $value;
            }
        }

        $stmt->closeCursor();

        return $table;
    }

    private function getAffectedRows($stmt)
    {
        if (!$stmt) {
            return 0;
        }

        $affected = $stmt->rowCount();

        $stmt->closeCursor();

        return $affected;
    }

    protected function increment($criteria)
    {
        return $this->getAffectedRows($this->query->increment($criteria));
    }

    protected function update($criteria)
    {
        return $this->getAffectedRows($this->query->update($criteria));
    }

    protected function delete($criteria)
    {
        return $this->getAffectedRows($this->query->delete($criteria));
    }

    protected function insert($criteria, $onDuplicateUpdate = [])
    {
        return $this->getAffectedRows($this->query->insert($criteria, $onDuplicateUpdate));
    }
}
