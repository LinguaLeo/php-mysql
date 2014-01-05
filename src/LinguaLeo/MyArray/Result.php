<?php

namespace LinguaLeo\MyArray;

use LinguaLeo\DataQuery\ResultInterface;

class Result implements ResultInterface
{
    private $table;
    private $count;

    public function __construct($table, $count)
    {
        $this->table = $table;
        $this->count = $count;
    }

    public function column($number)
    {
        $fields = array_keys($this->table);
        return $this->table[$fields[$number]];
    }

    public function count()
    {
        return $this->count;
    }

    public function keyValue()
    {
        $fields = array_keys($this->table);
        return array_combine($this->table[$fields[0]], $this->table[$fields[1]]);
    }

    public function many()
    {
        $rows = [];
        foreach ($this->table as $field => $column) {
            foreach ($column as $i => $value) {
                $rows[$i][$field] = $value;
            }
        }
        return $rows;
    }

    public function one()
    {
        $row = [];
        foreach ($this->table as $field => $column) {
            $row[$field] = reset($column);
        }
        return $row;
    }

    public function table()
    {
        return $this->table;
    }

    public function value($name)
    {
        if (isset($this->table[$name])) {
            return reset($this->table[$name]);
        }
        return null;
    }

    public function free()
    {
        $this->table = null;
        $this->count = 0;
        return true;
    }
}