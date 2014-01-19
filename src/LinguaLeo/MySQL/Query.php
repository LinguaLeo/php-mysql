<?php

namespace LinguaLeo\MySQL;

use LinguaLeo\DataQuery\Exception\QueryException;
use LinguaLeo\DataQuery\Criteria;
use LinguaLeo\DataQuery\QueryInterface;

class Query implements QueryInterface
{
    /**
     * @var Pool
     */
    private $pool;

    /**
     * @var Routing
     */
    private $routing;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @var Route
     */
    private $route;

    /**
     * Instantiate the query
     *
     * @param Pool $pool
     * @param Routing $routing
     */
    public function __construct($pool, $routing)
    {
        $this->pool = $pool;
        $this->routing = $routing;
    }

    private function getPlaceholders($count, $placeholder = '?')
    {
        return implode(',', array_fill(0, $count, $placeholder));
    }

    private function getFrom(Criteria $criteria)
    {
        return $this->route = $this->routing->getRoute($criteria);
    }

    private function getOrder($orderBy)
    {
        static $typesMap = [SORT_ASC => 'ASC', SORT_DESC => 'DESC'];
        $keys = [];
        foreach ((array)$orderBy as $field => $sortType) {
            if (empty($typesMap[$sortType])) {
                throw new QueryException(sprintf('Unknown %s sort type', $sortType));
            }
            $keys[] = $field . ' ' . $typesMap[$sortType];
        }
        return implode(', ', $keys);
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

    private function getExpression($fields)
    {
        if (empty($fields)) {
            return '*';
        }
        foreach ($fields as $field => &$aggregate) {
            if (is_string($field)) {
                $aggregate = strtoupper($aggregate).'('.$field.')';
            }
        }
        return implode(',', $fields);
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
     * {@inheritdoc}
     */
    public function select(Criteria $criteria)
    {
        $SQL = 'SELECT ' . $this->getExpression($criteria->fields)
            . ' FROM ' . $this->getFrom($criteria)
            . ' WHERE ' . $this->getWhere($criteria);

        if ($criteria->orderBy) {
            $SQL .= ' ORDER BY '. $this->getOrder($criteria->orderBy);
        }

        if ($criteria->limit) {
            $SQL .= ' LIMIT ' . (int)$criteria->limit;
            if ($criteria->offset) {
                $SQL .= ' OFFSET ' . (int)$criteria->offset;
            }
        }

        return $this->executeQuery($SQL, $this->arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function insert(Criteria $criteria)
    {
        if (empty($criteria->fields)) {
            throw new QueryException('No fields for insert statement');
        }

        $SQL = 'INSERT INTO ' . $this->getFrom($criteria) .
            '(' . implode(',', $criteria->fields) . ') VALUES ' . $this->getValuesPlaceholders($criteria);

        if ($criteria->upsert) {
            $SQL .= ' ON DUPLICATE KEY UPDATE ' . $this->getDuplicateUpdatedValues($criteria->upsert);
        }

        return $this->executeQuery($SQL, $this->arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Criteria $criteria)
    {
        $SQL = 'DELETE FROM ' . $this->getFrom($criteria) . ' WHERE ' . $this->getWhere($criteria);
        return $this->executeQuery($SQL, $this->arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function update(Criteria $criteria)
    {
        return $this->executeUpdate($criteria, function ($fields) {
            return implode('=?,', $fields) . '=?';
        });
    }

    /**
     * {@inheritdoc}
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

        return $this->executeQuery($SQL, array_merge($criteria->values, $this->arguments));
    }

    /**
     * Executes the query with parameters
     *
     * @param string $query
     * @param array $params
     * @return \LinguaLeo\DataQuery\ResultInterface
     */
    protected function executeQuery($query, $params = [])
    {
        $force = false;
        do {
            try {
                return $this->getResult($this->pool->connect($this->route->getDbName(), $force), $query, $params);
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
     * @return \LinguaLeo\DataQuery\ResultInterface
     */
    private function getResult($conn, $query, $params)
    {
        if ($params) {
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
        } else {
            $stmt = $conn->query($query);
        }
        return new Result($stmt);
    }
}
