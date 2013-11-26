<?php
namespace LinguaLeo\MySQL;


class PeerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\MysqlNotFoundException
     */
    public function testSelectOneException()
    {
        // GIVEN
        $pdoStatementMock = $this->getPDOStatementMock(false);
        $queryMock = $this->getQueryMock($pdoStatementMock);
        $criteriaMock = $this->getCriteriaMock();

        // WHEN
        $peer = new Peer($queryMock, 'dbName', 'tableName');

        $method = new \ReflectionMethod('\LinguaLeo\MySQL\Peer', 'selectOne');
        $method->setAccessible(TRUE);

        $row = $method->invoke($peer, $criteriaMock);

        // THEN
        // exception will be thrown

    }

    public function testSelectOne()
    {
        // GIVEN
        $sampleRow = ['foo' => 'bar'];
        $pdoStatementMock = $this->getPDOStatementMock($sampleRow);
        $queryMock = $this->getQueryMock($pdoStatementMock);
        $criteriaMock = $this->getCriteriaMock();

        // WHEN
        $peer = new Peer($queryMock, 'dbName', 'tableName');

        $method = new \ReflectionMethod('\LinguaLeo\MySQL\Peer', 'selectOne');
        $method->setAccessible(TRUE);

        $row = $method->invoke($peer, $criteriaMock);

        // THEN
        $this->assertSame($sampleRow, $row);
    }

    /**
     * @param $returnValue
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPDOStatementMock($returnValue)
    {
        $pdoStatementMock = $this->getMock('PDOStatement');
        $pdoStatementMock
            ->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($returnValue));

        return $pdoStatementMock;
    }

    /**
     * @param $pdoStatementMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getQueryMock($pdoStatementMock)
    {
        $queryMock = $this->getMockBuilder('LinguaLeo\MySQL\Query')
            ->disableOriginalConstructor()
            ->getMock();
        $queryMock
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($pdoStatementMock));

        return $queryMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCriteriaMock()
    {
        $criteriaMock = $this->getMockBuilder('LinguaLeo\MySQL\Criteria')
            ->disableOriginalConstructor()
            ->getMock();

        return $criteriaMock;
    }
} 