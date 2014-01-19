<?php

namespace LinguaLeo\MySQL;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    protected $route;

    public function setUp()
    {
        parent::setUp();
        $this->route = new Route('foo', 'bar');
    }

    public function testGetDbName()
    {
        $this->assertSame('foo', $this->route->getDbName());
    }

    public function testGetTableName()
    {
        $this->assertSame('bar', $this->route->getTableName());
    }

    public function testToString()
    {
        $this->assertSame('foo.bar', (string) $this->route);
    }
}