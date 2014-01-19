<?php

namespace LinguaLeo\MySQL;

use LinguaLeo\DataQuery\Criteria;
use LinguaLeo\MySQL\Exception\RoutingException;

class Routing
{
    private $primaryDbName;
    private $tablesMap;

    private static $convertMap = [
        'chunked' => ['table_name', 'chunk_id'],
        'spotted' => ['db', 'spot_id'],
        'localized' => ['db', 'locale'],
    ];

    public function __construct($primaryDbName, array $tablesMap)
    {
        $this->primaryDbName = $primaryDbName;
        $this->tablesMap = $tablesMap;
    }

    /**
     * Prepare a route for a table
     *
     * @param Criteria $criteria
     * @return Route
     * @throws RoutingException
     */
    public function getRoute(Criteria $criteria)
    {
        $entry = $this->getEntry($criteria->location);
        if (isset($entry['options'])) {
            foreach ((array)$entry['options'] as $type => $options) {
                if (is_int($type)) {
                    $type = $options;
                    $options = null;
                }
                list($placeholder, $parameter) = $this->getConvertOptions($type);
                $entry[$placeholder] = $this->getLocation($entry[$placeholder], $criteria->getMeta($parameter), $options);
            }
        }
        return new Route($entry['db'], $entry['table_name']);
    }

    /**
     * Find an entry by table name
     *
     * @param string $tableName
     * @return array
     */
    private function getEntry($tableName)
    {
        $default = ['db' => $this->primaryDbName, 'table_name' => $tableName];
        if (empty($this->tablesMap[$tableName])) {
            return $default;
        }
        return (array)$this->tablesMap[$tableName] + $default;
    }

    /**
     * Returns convert options by type
     *
     * @param string $type
     * @return array(string,string) placeholder & parameter
     * @throws RoutingException
     */
    private function getConvertOptions($type)
    {
        if (empty(self::$convertMap[$type])) {
            throw new RoutingException(sprintf('Unknown "%s" option type', $type));
        }
        return self::$convertMap[$type];
    }

    /**
     * Builds a location by modifier
     *
     * @param string $location
     * @param mixed $modifier
     * @param mixed $options
     * @return string
     * @throws RoutingException
     */
    private function getLocation($location, $modifier, $options)
    {
        if (isset($options['not']) && in_array($modifier, (array)$options['not'])) {
            return $location;
        }
        if (empty($options['as']) || 'suff' === $options['as']) {
            return $location.'_'.$modifier;
        }
        if ('pref' === $options['as']) {
            return $modifier.'_'.$location;
        }
        throw new RoutingException(sprintf('Unknown "%s" constant for "as" operator', $options['as']));
    }
}