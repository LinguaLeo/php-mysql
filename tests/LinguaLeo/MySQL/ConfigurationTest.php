<?php

namespace LinguaLeo\MySQL;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    private $configuration;

    public function setUp()
    {
        parent::setUp();

        $this->configuration = new Configuration(['db_name' => 'hostname'], 'root', 'Pa$$w0rd');
    }

    public function testGetUser()
    {
        $this->assertSame('root', $this->configuration->getUser());
    }

    public function testGetPassword()
    {
        $this->assertSame('Pa$$w0rd', $this->configuration->getPasswd());
    }

    public function testGetHost()
    {
        $this->assertSame('hostname', $this->configuration->getHost('db_name'));
    }

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\PoolException
     * @expectedExceptionMessage Host is not defined for ololo database
     */
    public function testGetUndefinedHost()
    {
        $this->configuration->getHost('ololo');
    }
}