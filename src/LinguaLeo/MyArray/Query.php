<?php

namespace LinguaLeo\MyArray;

use LinguaLeo\DataQuery\Criteria;
use LinguaLeo\DataQuery\QueryInterface;
use LinguaLeo\DataQuery\Exception\QueryException;

class Query implements QueryInterface
{
    private $table;

    public function __construct(array $table = [])
    {
        $this->table = $table;
    }

    private function getRowsCount($table)
    {
        if (empty($table)) {
            return 0;
        }
        return count(reset($table));
    }

    private function getRowsIndeces($conditions)
    {
        if (empty($conditions)) {
            return false;
        }
        $indeces = [];
        foreach ($conditions as $condition) {
            list($column, $value, $comparison) = $condition;
            if (!isset($this->table[$column])) {
                throw new QueryException(sprintf('The %s column not found', $column));
            }
            foreach ($this->table[$column] as $index => $rowValue) {
                if ($this->isEqual($rowValue, $value, $comparison)) {
                    $indeces[$column][] = $index;
                }
            }
        }
        switch (count($indeces)) {
            case 0: return [];
            case 1: return reset($indeces);
            default: return call_user_func_array('array_intersect', $indeces);
        }
    }

    private function isEqual($rowValue, $conditionValue, $comparison)
    {
        switch ($comparison) {
            case Criteria::IS_NOT_NULL: return null !== $rowValue;
            case Criteria::IS_NULL: return null === $rowValue;
            case Criteria::IN: return in_array($rowValue, (array)$conditionValue);
            case Criteria::NOT_IN: return !in_array($rowValue, (array)$conditionValue);
            case Criteria::EQUAL: return $rowValue == $conditionValue;
            case Criteria::EQUAL_GREATER: return $rowValue >= $conditionValue;
            case Criteria::EQUAL_LESS: return $rowValue <= $conditionValue;
            case Criteria::GREATER: return $rowValue > $conditionValue;
            case Criteria::LESS: return $rowValue < $conditionValue;
            case Criteria::NOT_EQUAL: return $rowValue != $conditionValue;
            default:
                throw new QueryException(sprintf('Unsupported %s comparison operator', $comparison));
        }
    }

    private function &castArray(&$value)
    {
        if (is_array($value)) {
            return $value;
        }
        return $value = [$value];
    }

    protected function getMappedTable($table, $fields)
    {
        if (empty($fields)) {
            return $table;
        }
        return array_intersect_key($table, array_flip($fields));
    }

    private function executeUpdate(Criteria $criteria, callable $processor)
    {
        if (!$criteria->fields) {
            throw new QueryException('No fields for update statement');
        }

        $indeces = $this->getRowsIndeces($criteria->conditions);

        $affectedRows = [];

        if (false == $indeces) {
            $indeces = array_keys(reset($this->table));
        }

        foreach ($criteria->fields as $i => $column) {
            if (!isset($this->table[$column])) {
                throw new QueryException(sprintf('The %s column not found', $column));
            }
            foreach ($indeces as $index) {
                if (call_user_func($processor, $column, $index, $criteria->values[$i])) {
                    $affectedRows[$index] = true;
                }
            }
        }

        return new Result(null, count($affectedRows));
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Criteria $criteria)
    {
        $indeces = $this->getRowsIndeces($criteria->conditions);
        if (false === $indeces) {
            $count = $this->getRowsCount($this->table);
            $this->table = [];
            return new Result(null, $count);
        }

        foreach ($this->table as &$column) {
            foreach ($indeces as $index) {
                unset($column[$index]);
            }
        }

        return new Result(null, count($indeces));
    }

    /**
     * {@inheritdoc}
     */
    public function increment(Criteria $criteria)
    {
        return $this->executeUpdate($criteria, function($column, $index, $value) {
            $this->table[$column][$index] += $value;
            return true;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function insert(Criteria $criteria)
    {
        $count = null;
        foreach ((array)$criteria->fields as $index => $field) {
            $value = $this->castArray($criteria->values[$index]);
            if (empty($this->table[$field])) {
                $this->table[$field] = $value;
            } else {
                $this->table[$field] = array_merge($this->table[$field], $value);
            }
            if (null === $count) {
                $count = count($value);
            } elseif ($count !== count($value)) {
                throw new QueryException(sprintf('Wrong rows count in %s column', $field));
            }
        }
        return new Result(null, $count);
    }

    /**
     * {@inheritdoc}
     */
    public function select(Criteria $criteria)
    {
        $indeces = $this->getRowsIndeces($criteria->conditions);
        $table = $this->getMappedTable($this->table, $criteria->fields);
        if (false !== $indeces) {
            $flippedIndeces = array_flip($indeces);
            foreach ($table as &$column) {
                $column = array_intersect_key($column, $flippedIndeces);
            }
        }
        return new Result($table, $this->getRowsCount($table));
    }

    /**
     * {@inheritdoc}
     */
    public function update(Criteria $criteria)
    {
        return $this->executeUpdate($criteria, function($column, $index, $value) {
            if ($this->table[$column][$index] != $value) {
                $this->table[$column][$index] = $value;
                return true;
            }
            return false;
        });
    }
}