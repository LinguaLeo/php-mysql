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

        // PDOStatement
        $pdoStatementMock = $this->getMock('PDOStatement');
        $pdoStatementMock
            ->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue(false));
        // Query
        $queryMock = $this->getMockBuilder('LinguaLeo\MySQL\Query')
            ->disableOriginalConstructor()
            ->getMock();
        $queryMock
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($pdoStatementMock));

        // Criteria
        $criteria = new Criteria('dbName', 'tableName');

        // WHEN
        $peer = new Peer($queryMock, 'dbName', 'tableName');
        $peer->selectOne($criteria);

        //THEN
        // exception will be thrown

    }

    public function testSelectOne()
    {
        // GIVEN

        // Sample row
        $sampleRow = ['foo' => 'bar'];

        // PDOStatement
        $pdoStatementMock = $this->getMock('PDOStatement');
        $pdoStatementMock
            ->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($sampleRow));

        // Query
        $queryMock = $this->getMockBuilder('LinguaLeo\MySQL\Query')
            ->disableOriginalConstructor()
            ->getMock();
        $queryMock
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($pdoStatementMock));

        // Criteria
        $criteria = new Criteria('dbName', 'tableName');

        // WHEN
        $peer = new Peer($queryMock, 'dbName', 'tableName');
        $row = $peer->selectOne($criteria);

        //THEN
        $this->assertSame($sampleRow, $row);
    }
} 