<?php

namespace LinguaLeo\MySQL;

use LinguaLeo\MySQL\Exception\QueryException;

class Query
{
    const EQUAL = ' = ';
    const EQUAL_GREATER = ' >= ';
    const EQUAL_LESS = ' <= ';
    const NOT_EQUAL = ' <> ';
    const GREATER = ' > ';
    const LESS = ' < ';
    const IN = ' IN ';
    const NOT_IN = ' NOT IN ';
    const CUSTOM = '#CUSTOM';
    const IS_NULL = ' IS NULL';
    const IS_NOT_NULL = ' IS NOT NULL';

    protected $pool;

    private $connectSchema;
    private $from;
    private $criteria;
    private $queryParams;

    /**
     * Instantiate the query
     *
     * @param Pool $pool
     */
    public function __construct($pool)
    {
        $this->pool = $pool;
    }

    private function cleanup()
    {
        $this->connectSchema = null;
        $this->from = [];
        $this->criteria = [];
        $this->queryParams = [];
    }

    public function table($tableName, $schemaName = null)
    {
        if (!$this->connectSchema) {
            $this->connectSchema = $schemaName;
        } elseif (!$schemaName) {
            $schemaName = $this->connectSchema;
        }
        if (!$schemaName) {
            throw new QueryException(
                sprintf('Schema is not defined for %s table', $tableName)
            );
        }
        $this->from[] = $schemaName.'.'.$tableName;
        return $this;
    }

    public function where($column, $value, $comparison = self::EQUAL)
    {
        $this->criteria[] = [$column, $value, $comparison];
        return $this;
    }

    /**
     * Run the SELECT query
     *
     * @param array $columns
     * @return \PDOStatement
     */
    public function select(array $columns = [])
    {
        $SQL = 'SELECT '.$this->implodeFragments($columns, '*')
            .' FROM '.$this->implodeFragments($this->from, 'DUAL')
            .' WHERE '.$this->getWhereSQLFragment();

        return $this->executeQuery($SQL, $this->queryParams);
    }

    /**
     * Run the UPDATE query
     *
     * @param array $columns
     * @return mixed
     */
    public function update(array $columns)
    {
        return $this->executeUpdate(
            implode(' = ?, ', array_keys($columns)).' = ?',
            array_values($columns)
        );
    }

    /**
     * Increment the columns by UPDATE query
     *
     * @param array $columns
     * @return mixed
     */
    public function increment(array $columns)
    {
        $placeholders = [];
        $values = [];

        foreach ($columns as $column => $value) {
            if (is_int($column)) {
                $placeholders[] = $value.' = '.$value.' + 1';
            } else {
                $placeholders[] = $column.' = '.$column.' + (?)';
                $values[] = $value;
            }
        }

        return $this->executeUpdate(implode(', ', $placeholders), $values);
    }

    private function executeUpdate($placeholders, array $values)
    {
        if (!$this->from) {
            throw new QueryException('Tables are not defined for update statement');
        }

        $SQL = 'UPDATE '.implode(', ', $this->from)
            .' SET '.$placeholders
            .' WHERE '.$this->getWhereSQLFragment();

        return $this->getAffectedRows(
            $this->executeQuery($SQL, array_merge($values, $this->queryParams))
        );
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

    private function implodeFragments($fragments, $default)
    {
        if (!$fragments) {
            return $default;
        }

        if (!is_array($fragments)) {
            throw new QueryException(
                sprintf('Bad data type for fragments, given %s', gettype($fragments))
            );
        }

        return implode(', ', $fragments);
    }

    private function getPlaceholders($count)
    {
        return implode(',', array_fill(0, $count, '?'));
    }

    private function getWhereSQLFragment()
    {
        $this->queryParams = [];

        if (!$this->criteria) {
            return 1;
        }

        $placeholders = [];

        foreach ($this->criteria as $criterion) {
            list($column, $value, $comparison) = $criterion;

            switch ($comparison) {
                case self::CUSTOM:
                    $placeholders[] = $column;
                    $this->queryParams[] = $value;
                    break;
                case self::IS_NOT_NULL:
                case self::IS_NULL:
                    $placeholders[] = $column.$comparison;
                    break;
                case self::IN:
                case self::NOT_IN:
                    $placeholders[] = $column.$comparison.'('.$this->getPlaceholders(count((array)$value)).')';
                    $this->queryParams = array_merge($this->queryParams, (array)$value);
                    break;
                default:
                    if (!is_scalar($value)) {
                        throw new QueryException(
                            sprintf(
                                'The %s type of value is wrong for %s comparison',
                                gettype($value),
                                $comparison
                            )
                        );
                    }
                    $placeholders[] = $column.$comparison.'?';
                    $this->queryParams[] = $value;
            }
        }

        return implode(' AND ', $placeholders);
    }

    /**
     * Run the INSERT query on single row
     *
     * @param array $row
     * @param array|string $onDuplicateUpdate
     * @return mixed
     * @throws QueryException
     */
    public function insert(array $row, $onDuplicateUpdate = [])
    {
        if (empty($this->from[0])) {
            throw new QueryException('A table is not defined for insert statement');
        }

        $SQL = 'INSERT INTO '.$this->from[0].'('.implode(',', array_keys($row)).')'
            .' VALUES ('.$this->getPlaceholders(count($row)).')';

        if ($onDuplicateUpdate) {
           $SQL .= ' ON DUPLICATE KEY UPDATE';
           foreach ((array)$onDuplicateUpdate as $column) {
               $SQL .= ' '.$column.' = VALUES('.$column.')';
           }
        }

        return $this->getAffectedRows($this->executeQuery($SQL, array_values($row)));
    }

    /**
     * Run the DELETE query
     *
     * @return mixed
     * @throws QueryException
     */
    public function delete()
    {
        if (empty($this->from[0])) {
            throw new QueryException('A table is not defined for delete statement');
        }

        $SQL = 'DELETE FROM '.$this->from[0].' WHERE '.$this->getWhereSQLFragment();

        return $this->getAffectedRows(
            $this->executeQuery($SQL, $this->queryParams)
        );
    }

    /**
     * Executes the query with parameters
     *
     * @param string $query
     * @param array $params
     * @return \PDOStatement
     */
    public function executeQuery($query, $params = [])
    {
        $conn = $this->pool->connect($this->connectSchema);
        if ($params) {
            $stmt = $conn->prepare($query);

            $stmt->execute($params);
        } else {
            $stmt = $conn->query($query);
        }

        $this->cleanup();

        return $stmt;
    }
}
