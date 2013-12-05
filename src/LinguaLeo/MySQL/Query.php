<?php

namespace LinguaLeo\MySQL;

use LinguaLeo\MySQL\Exception\QueryException;

class Query
{
    private $pool;

    private $arguments;

    /**
     * Instantiate the query
     *
     * @param Pool $pool
     */
    public function __construct($pool)
    {
        $this->pool = $pool;
    }

    private function getPlaceholders($count, $placeholder = '?')
    {
        return implode(',', array_fill(0, $count, $placeholder));
    }

    private function getFrom(Criteria $criteria)
    {
        return $criteria->dbName . '.' . $criteria->tableName;
    }

    private function getWhere(Criteria $criteria)
    {
        $this->arguments = [];

        if (empty($criteria->conditions)) {
            return 1;
        }

        $placeholders = [];

        foreach ($criteria->conditions as $condition) {
            list($column, $value, $comparison) = $condition;

            switch ($comparison) {
                case Criteria::CUSTOM:
                    $placeholders[] = $column;
                    $this->arguments[] = $value;
                    break;
                case Criteria::IS_NOT_NULL:
                case Criteria::IS_NULL:
                    $placeholders[] = $column . ' ' . $comparison;
                    break;
                case Criteria::IN:
                case Criteria::NOT_IN:
                    $placeholders[] = $column . ' ' . $comparison . '(' . $this->getPlaceholders(count((array)$value)) . ')';
                    $this->arguments = array_merge($this->arguments, (array)$value);
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
                    $placeholders[] = $column . $comparison . '?';
                    $this->arguments[] = $value;
            }
        }

        return implode(' AND ', $placeholders);
    }

    public function count(Criteria $criteria)
    {
        $SQL = 'SELECT COUNT(*) FROM ' . $this->getFrom($criteria) . ' WHERE ' . $this->getWhere($criteria);
        return $this->executeQuery($criteria->dbName, $SQL, $this->arguments);
    }

    /**
     * Run the SELECT query
     *
     * @param Criteria $criteria
     * @return \PDOStatement
     */
    public function select(Criteria $criteria)
    {
        if (empty($criteria->fields)) {
            $columns = '*';
        } else {
            $columns = implode(',', $criteria->fields);
        }

        $SQL = 'SELECT ' . $columns
            . ' FROM ' . $this->getFrom($criteria)
            . ' WHERE ' . $this->getWhere($criteria);

        if ($criteria->limit) {
            $SQL .= ' LIMIT ' . (int)$criteria->offset . ',' . (int)$criteria->limit;
        }

        return $this->executeQuery($criteria->dbName, $SQL, $this->arguments);
    }

    /**
     * Run the INSERT query on single row
     *
     * @param Criteria $criteria
     * @param array|string $onDuplicateUpdate
     * @return \PDOStatement
     * @throws QueryException
     */
    public function insert(Criteria $criteria, $onDuplicateUpdate = [])
    {
        if (empty($criteria->fields)) {
            throw new QueryException('No fields for insert statement');
        }

        $SQL = 'INSERT INTO ' . $this->getFrom($criteria) .
            '(' . implode(',', $criteria->fields) . ') VALUES ' . $this->getValuesPlaceholders($criteria);

        if ($onDuplicateUpdate) {
            $SQL .= ' ON DUPLICATE KEY UPDATE ' . $this->getDuplicateUpdatedValues($onDuplicateUpdate);
        }

        return $this->executeQuery($criteria->dbName, $SQL, $this->arguments);
    }

    /**
     * Generate VALUES part of INSERT query
     *
     * @param Criteria $criteria
     * @return string
     * @throws QueryException
     */
    private function getValuesPlaceholders(Criteria $criteria)
    {
        $this->arguments = [];
        $columnsCount = count($criteria->fields);
        $rowsCount = null;
        foreach ($criteria->values as $columnIndex => $column) {
            foreach ((array)$column as $rowIndex => $value) {
                $this->arguments[$columnIndex + $rowIndex * $columnsCount] = $value;
            }
            if (null === $rowsCount) {
                $rowsCount = $rowIndex + 1;
            } elseif ($rowsCount !== $rowIndex + 1) {
                throw new QueryException(sprintf('Wrong rows count in %d column for multi insert query', $columnIndex));
            }
        }
        return $this->getPlaceholders($rowsCount, '('.$this->getPlaceholders($columnsCount).')');
    }

    /**
     * Get SQL fragment for DUPLICATE KEY UPDATE statement
     *
     * @param array|string $columns
     * @return string
     */
    private function getDuplicateUpdatedValues($columns)
    {
        $updates = [];
        foreach ((array)$columns as $column) {
            $updates[] = $column . '=VALUES(' . $column . ')';
        }
        return implode(',', $updates);
    }

    /**
     * Run the DELETE query
     *
     * @param Criteria $criteria
     * @return \PDOStatement
     * @throws QueryException
     */
    public function delete(Criteria $criteria)
    {
        $SQL = 'DELETE FROM ' . $this->getFrom($criteria) . ' WHERE ' . $this->getWhere($criteria);
        return $this->executeQuery($criteria->dbName, $SQL, $this->arguments);
    }

    /**
     * Run the UPDATE query
     *
     * @param Criteria $criteria
     * @return \PDOStatement
     */
    public function update(Criteria $criteria)
    {
        return $this->executeUpdate($criteria, function ($fields) {
            return implode('=?,', $fields) . '=?';
        });
    }

    /**
     * Increment the columns by UPDATE query
     *
     * @param Criteria $criteria
     * @return \PDOStatement
     */
    public function increment(Criteria $criteria)
    {
        return $this->executeUpdate($criteria, function ($fields) {
            $placeholders = [];

            foreach ($fields as $field) {
                $placeholders[] = $field . '=' . $field . '+(?)';
            }

            return implode(',', $placeholders);
        });
    }

    private function executeUpdate(Criteria $criteria, callable $placeholdersGenerator)
    {
        if (!$criteria->fields) {
            throw new QueryException('No fields for update statement');
        }

        $SQL = 'UPDATE ' . $this->getFrom($criteria)
            . ' SET ' . call_user_func($placeholdersGenerator, $criteria->fields)
            . ' WHERE ' . $this->getWhere($criteria);

        return $this->executeQuery(
            $criteria->dbName,
            $SQL,
            array_merge($criteria->values, $this->arguments)
        );
    }

    /**
     * Executes the query with parameters
     *
     * @param string $dbName
     * @param string $query
     * @param array $params
     * @return \PDOStatement
     */
    public function executeQuery($dbName, $query, $params = [])
    {
        $force = false;
        do {
            try {
                return $this->getStatement($this->pool->connect($dbName, $force), $query, $params);
            } catch (\PDOException $e) {
                $force = $this->hideQueryException($e, $force);
            }
        } while (true);
    }

    /**
     * Prevent query exception
     *
     * @param \PDOException $e
     * @param boolean $force
     * @return boolean
     * @throws \PDOException
     */
    private function hideQueryException(\PDOException $e, $force)
    {
        list($generalError, $code, $message) = $e->errorInfo;
        switch ($code) {
            case 2006: // MySQL server has gone away
            case 2013: // Lost connection to MySQL server during query
                if (!$force) {
                    return true;
                }
            default:
                throw $e;
        }
    }

    /**
     * Run the query
     *
     * @param Connection $conn
     * @param string $query
     * @param array $params
     * @return \PDOStatement
     */
    protected function getStatement($conn, $query, $params)
    {
        if ($params) {
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
        } else {
            $stmt = $conn->query($query);
        }

        return $stmt;
    }

    /**
     * Returns last inserted identifier
     *
     * @param \LinguaLeo\MySQL\Criteria $criteria
     * @return string
     */
    public function getLastInsertId(Criteria $criteria)
    {
        return $this->pool->connect($criteria->dbName)->lastInsertId();
    }
}
