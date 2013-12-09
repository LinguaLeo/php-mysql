<?php

namespace LinguaLeo\DataQuery;

interface QueryInterface
{
    /**
     * Read data from a storage
     *
     * @param Criteria $criteria
     * @return ResultInterface
     */
    public function select(Criteria $criteria);

    /**
     * Insert new data to a storage
     *
     * @param Criteria $criteria
     * @return ResultInterface
     */
    public function insert(Criteria $criteria);

    /**
     * Delete data from a storage
     *
     * @param Criteria $criteria
     * @return ResultInterface
     */
    public function delete(Criteria $criteria);

    /**
     * Update data from a storage
     *
     * @param Criteria $critera
     * @return ResultInterface
     */
    public function update(Criteria $criteria);

    /**
     * Increment data values from a storage
     *
     * @param Criteria $criteria
     * @return ResultInterface
     */
    public function increment(Criteria $criteria);
}