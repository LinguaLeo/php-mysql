<?php

namespace LinguaLeo\MySQL\HS;

use LinguaLeo\MySQL\Criteria;
use LinguaLeo\MySQL\Exception\QueryException;

class Query
{
    private $readPool;
    private $writePool;

    /**
     * Instantiate the query
     *
     * @param Pool $readPool
     * @param Pool $writePool
     */
    public function __construct($readPool, $writePool)
    {
        $this->readPool = $readPool;
        $this->writePool = $writePool;
    }

    private function getResponse($socket)
    {
        $result = $socket->readResponse();

        if ($result instanceof \HSPHP\ErrorMessage) {
            throw $result;
        }

        return $result;
    }

    /**
     * Run the SELECT query
     *
     * @param \LinguaLeo\MySQL\Criteria $criteria
     * @return array
     * @throws QueryException
     */
    public function select(Criteria $criteria)
    {
        if (empty($criteria->fields)) {
            throw new QueryException('No fields for the select statement');
        }

        if (empty($criteria->conditions)) {
            throw new QueryException('No condition for the select statement');
        }

        list($key, $value, $comparison) = $criteria->conditions[0];

        $socket = $this->readPool->connect($criteria->dbName);
        /* @var $socket \HSPHP\ReadSocket */

        $index = $socket->getIndexId(
            $criteria->dbName,
            $criteria->tableName,
            $key,
            implode(',', $criteria->fields)
        );

        switch ($comparison) {
            case Criteria::IN:
                $begin = (int)$criteria->offset;
                $limit = $criteria->limit ?: 1;
                $socket->select($index, '=', [0], $begin + $limit, $begin, $value);
                break;
            case Criteria::EQUAL:
            case Criteria::EQUAL_GREATER:
            case Criteria::EQUAL_LESS:
            case Criteria::GREATER:
            case Criteria::LESS:
                $socket->select($index, $comparison, [$value]);
                break;
            default:
                throw new QueryException(sprintf('Unsupported %s comparison type', $comparison));
        }

        return $this->getResponse($socket);
    }

    /**
     * Run the UPDATE query
     *
     * @param \LinguaLeo\MySQL\Criteria $criteria
     * @return array
     * @throws QueryException
     */
    public function update(Criteria $criteria)
    {
        if (empty($criteria->fields)) {
            throw new QueryException('No fields for the update statement');
        }

        if (empty($criteria->conditions)) {
            throw new QueryException('No condition for the update statement');
        }

        list($key, $value, $comparison) = $criteria->conditions[0];

        $socket = $this->writePool->connect($criteria->dbName);
        /* @var $socket \HSPHP\WriteSocket */

        $index = $socket->getIndexId(
            $criteria->dbName,
            $criteria->tableName,
            $key,
            implode(',', $criteria->fields)
        );

        switch ($comparison) {
            case Criteria::EQUAL:
            case Criteria::EQUAL_GREATER:
            case Criteria::EQUAL_LESS:
            case Criteria::GREATER:
            case Criteria::LESS:
                $socket->update($index, $comparison, [$value], $criteria->values);
                break;
            default:
                throw new QueryException(sprintf('Unsupported %s comparison type', $comparison));
        }

        return $this->getResponse($socket);
    }

    /**
     * Run the INSERT query
     *
     * @param \LinguaLeo\MySQL\Criteria $criteria
     * @return array
     * @throws QueryException
     */
    public function insert(Criteria $criteria)
    {
        if (empty($criteria->fields)) {
            throw new QueryException('No fields for the insert statement');
        }

        $socket = $this->writePool->connect($criteria->dbName);
        /* @var $socket \HSPHP\WriteSocket */

        $index = $socket->getIndexId(
            $criteria->dbName,
            $criteria->tableName,
            '',
            implode(',', $criteria->fields)
        );

        $socket->insert($index, $criteria->values);

        return $this->getResponse($socket);
    }

    /**
     * Run the DELETE query
     *
     * @param \LinguaLeo\MySQL\Criteria $criteria
     * @return array
     * @throws QueryException
     */
    public function delete(Criteria $criteria)
    {
        if (empty($criteria->conditions)) {
            throw new QueryException('No condition for the update statement');
        }

        list($key, $value, $comparison) = $criteria->conditions[0];

        $socket = $this->writePool->connect($criteria->dbName);
        /* @var $socket \HSPHP\WriteSocket */

        $index = $socket->getIndexId(
            $criteria->dbName,
            $criteria->tableName,
            $key,
            ''
        );

        switch ($comparison) {
            case Criteria::EQUAL:
            case Criteria::EQUAL_GREATER:
            case Criteria::EQUAL_LESS:
            case Criteria::GREATER:
            case Criteria::LESS:
                $socket->delete($index, $comparison, [$value]);
                break;
            default:
                throw new QueryException(sprintf('Unsupported %s comparison type', $comparison));
        }

        return $this->getResponse($socket);
    }
}