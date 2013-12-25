<?php

namespace LinguaLeo\MySQL;

use LinguaLeo\MySQL\Exception\RoutingException;

class Routing
{
    private $dbName;
    private $map;
    private $arguments;

    private static $convertMap = [
        'chunked' => ['table_name', 'chunk_id'],
        'spotted' => ['db', 'spot_id'],
        'localized' => ['db', 'locale'],
    ];

    public function __construct($dbName, array $map)
    {
        $this->dbName = $dbName;
        $this->map = $map;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    public function getArgument($name)
    {
        if (isset($this->arguments[$name])) {
            return $this->arguments[$name];
        }
        throw new RoutingException(sprintf('The "%s" parameter is required', $name));
    }

    /**
     * Prepare a route for a table
     *
     * @param string $tableName
     * @return array(string,string)
     * @throws RoutingException
     */
    public function getRoute($tableName)
    {
        $entry = $this->getEntry($tableName);
        if (isset($entry['options'])) {
            foreach ((array)$entry['options'] as $type => $options) {
                if (is_int($type)) {
                    $type = $options;
                    $options = null;
                }
                $this->updateEntry($entry, $type, $options);
            }
        }
        return [$entry['db'], $entry['table_name']];
    }

    /**
     * Find an entry by table name
     *
     * @param string $tableName
     * @return array
     * @throws RoutingException
     */
    private function getEntry($tableName)
    {
        if (!array_key_exists($tableName, $this->map)) {
            throw new RoutingException(sprintf('Unknown "%s" table name', $tableName));
        }
        return (array)$this->map[$tableName] + ['db' => $this->dbName, 'table_name' => $tableName];
    }

    /**
     * Update entry by convert map
     *
     * @param array $entry
     * @param string $type
     * @param array $options
     * @throws RoutingException
     */
    private function updateEntry(&$entry, $type, $options)
    {
        if (empty(self::$convertMap[$type])) {
            throw new RoutingException(sprintf('Unknown "%s" option type', $type));
        }
        list($placeholder, $parameter) = self::$convertMap[$type];
        $entry[$placeholder] = $this->getLocation($entry[$placeholder], $this->getArgument($parameter), $options);
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
        if (empty($options['as'])) {
            $options['as'] = 'suff';
        }
        switch ($options['as']) {
            case 'pref': return $modifier.'_'.$location;
            case 'suff': return $location.'_'.$modifier;
            default:
                throw new RoutingException(sprintf('Unknown "%s" constant for "as" operator', $options['as']));
        }
        return $location;
    }
}