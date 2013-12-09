<?php

namespace LinguaLeo\DataQuery;

interface ResultInterface extends \Countable
{
    /**
     * Returns a hash
     *
     * @return array
     */
    public function keyValue();

    /**
     * Returns a row
     *
     * @return array
     */
    public function one();

    /**
     * Returns a value
     *
     * @return mixed
     */
    public function value($name);

    /**
     * Returns an array of rows
     *
     * @return array
     */
    public function many();

    /**
     * Returns an array of columns
     *
     * @return array
     */
    public function table();

    /**
     * Returns a column
     *
     * @return array
     */
    public function column($number);
}