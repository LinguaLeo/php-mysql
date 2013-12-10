<?php

namespace LinguaLeo\MySQL;

use LinguaLeo\MySQL\Exception\CriteriaException;

class Criteria
{
    const EQUAL = '=';
    const EQUAL_GREATER = '>=';
    const EQUAL_LESS = '<=';
    const NOT_EQUAL = '<>';
    const GREATER = '>';
    const LESS = '<';
    const IN = 'IN';
    const NOT_IN = 'NOT IN';
    const CUSTOM = '#CUSTOM';
    const IS_NULL = 'IS NULL';
    const IS_NOT_NULL = 'IS NOT NULL';

    public $dbName;
    public $tableName;
    public $conditions;
    public $limit;
    public $offset;
    public $fields;
    public $values;
    public $orderBy;

    public function __construct($dbName, $tableName)
    {
        $this->dbName = $dbName;
        $this->tableName = $tableName;
    }

    public function where($column, $value, $comparison = self::EQUAL)
    {
        $this->conditions[] = [$column, $value, $comparison];
        return $this;
    }

    public function limit($limit, $offset = 0)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    public function read(array $fields)
    {
        $this->fields = $fields;
        return $this;
    }

    public function write(array $values)
    {
        $this->fields = array_keys($values);
        $this->values = array_values($values);
        return $this;
    }

    public function writePipe(array $values)
    {
        if (empty($this->fields)) {
            return $this->write($values);
        }
        foreach ($this->fields as $index => $name) {
            if (!array_key_exists($name, $values)) {
                throw new CriteriaException(sprintf('The field %s not found in values', $name));
            }
            $this->castArray($this->values[$index])[] = $values[$name];
        }
        return $this;
    }

    public function orderBy($field, $sortType = SORT_ASC)
    {
        $this->orderBy[$field] = $sortType;
        return $this;
    }

    private function &castArray(&$value)
    {
        $value = (array)$value;
        return $value;
    }
}