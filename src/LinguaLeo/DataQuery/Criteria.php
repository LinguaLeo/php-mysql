<?php

namespace LinguaLeo\DataQuery;

use LinguaLeo\DataQuery\Exception\CriteriaException;

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
    const IS_NULL = 'IS NULL';
    const IS_NOT_NULL = 'IS NOT NULL';

    protected $meta;

    public $location;
    public $conditions;
    public $limit;
    public $offset;
    public $fields;
    public $values;
    public $orderBy;
    public $upsert;

    public function __construct($location, array $meta = [])
    {
        $this->location = $location;
        $this->meta = $meta;
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

    public function upsert(array $upsert)
    {
        $this->upsert = $upsert;
        return $this;
    }

    public function orderBy($field, $sortType = SORT_ASC)
    {
        $this->orderBy[$field] = $sortType;
        return $this;
    }

    private function &castArray(&$value)
    {
        if (is_array($value)) {
            return $value;
        }
        return $value = [$value];
    }

    public function getMeta($name)
    {
        if (!isset($this->meta[$name])) {
            throw new CriteriaException(sprintf('The %s meta value not found', $name));
        }
        return $this->meta[$name];
    }

    public function setMeta($name, $value)
    {
        $this->meta[$name] = $value;
        return $this;
    }
}
