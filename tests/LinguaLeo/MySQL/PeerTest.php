<?php
namespace LinguaLeo\MySQL;


class PeerTest extends \PHPUnit_Framework_TestCase
{
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
        $method->setAccessible(true);

        $row = $method->invoke($peer, $criteriaMock);

        // THEN
        $this->assertSame($sampleRow, $row);
    }

    public function testMultiInsertCriteriaList()
    {
        // GIVEN
        $pdoStatementMock = $this->getPDOStatementMock(null, 5);
        $queryMock = $this->getQueryMock($pdoStatementMock);
        $criteriaMock1 = $this->getCriteriaMock();
        $criteriaMock2 = $this->getCriteriaMock();

        // WHEN
        $peer = new Peer($queryMock, 'dbName', 'tableName');

        $method = new \ReflectionMethod('\LinguaLeo\MySQL\Peer', 'multiInsertCriteriaList');
        $method->setAccessible(true);

        $count = $method->invoke($peer, [$criteriaMock1, $criteriaMock2]);

        // THEN
        $this->assertSame(5, $count);

    }


    public function testMultiInsertAssoc()
    {
        // GIVEN
        $pdoStatementMock = $this->getPDOStatementMock(null, 2);
        $queryMock = $this->getQueryMock($pdoStatementMock);

        $cr1 = new Criteria('dbName', 'tableName');
        $cr1->write(['foo' => 1, 'bar' => 2]);

        $cr2 = new Criteria('dbName', 'tableName');
        $cr2->write(['foo' => 3, 'bar' => 4]);

        $queryMock
            ->expects($this->once())
            ->method('multiInsert')
            ->with(
                $this->equalTo([$cr1, $cr2]),
                $this->equalTo(['bar'])
            );

        // WHEN
        $peer = new Peer($queryMock, 'dbName', 'tableName');

        $method = new \ReflectionMethod('\LinguaLeo\MySQL\Peer', 'multiInsertAssoc');
        $method->setAccessible(true);



        $count = $method->invoke(
            $peer,
            [
                ['foo' => 1, 'bar' => 2],
                ['foo' => 3, 'bar' => 4]
            ],
            ['bar']
        );

        // THEN
        $this->assertSame(2, $count);

    }

    public function testMultiInsertValues()
    {
        // GIVEN
        $pdoStatementMock = $this->getPDOStatementMock(null, 2);
        $queryMock = $this->getQueryMock($pdoStatementMock);

        $cr1 = new Criteria('dbName', 'tableName');
        $cr1->write(['foo' => 1, 'bar' => 2]);

        $cr2 = new Criteria('dbName', 'tableName');
        $cr2->write(['foo' => 3, 'bar' => 4]);

        $queryMock
            ->expects($this->once())
            ->method('multiInsert')
            ->with(
                $this->equalTo([$cr1, $cr2]),
                $this->equalTo(['bar'])
            );


        // WHEN
        $peer = new Peer($queryMock, 'dbName', 'tableName');

        $method = new \ReflectionMethod('\LinguaLeo\MySQL\Peer', 'multiInsertValues');
        $method->setAccessible(true);

        $count = $method->invoke(
            $peer,
            ['foo', 'bar'],
            [
                [1, 2],
                [3, 4]
            ],
            ['bar']
        );

        // THEN
        $this->assertSame(2, $count);

    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Count of fields and values does not match
     */
    public function testMultiInsertValuesException()
    {
        // GIVEN
        $pdoStatementMock = $this->getPDOStatementMock(null, 2);
        $queryMock = $this->getQueryMock($pdoStatementMock);

        // WHEN
        $peer = new Peer($queryMock, 'dbName', 'tableName');

        $method = new \ReflectionMethod('\LinguaLeo\MySQL\Peer', 'multiInsertValues');
        $method->setAccessible(true);

        $count = $method->invoke(
            $peer,
            ['foo', 'bar'],
            [
                [1, 2],
                [3, 4, 5]
            ],
            ['bar']
        );

        // THEN
        $this->assertSame(2, $count);

    }

    /**
     * @param $returnValue
     * @param null $affected
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPDOStatementMock($returnValue, $affected = null)
    {
        $pdoStatementMock = $this->getMock('PDOStatement');
        $pdoStatementMock
            ->expects($this->any())
            ->method('fetch')
            ->will($this->returnValue($returnValue));

        if (isset($affected)) {
            $pdoStatementMock
                ->expects($this->any())
                ->method('rowCount')
                ->will($this->returnValue($affected));
        }

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
            ->expects($this->any())
            ->method('select')
            ->will($this->returnValue($pdoStatementMock));

        $queryMock
            ->expects($this->any())
            ->method('multiInsert')
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