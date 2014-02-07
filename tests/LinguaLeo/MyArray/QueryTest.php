<?php

namespace LinguaLeo\MyArray;

use LinguaLeo\DataQuery\Criteria;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Criteria
     */
    protected $criteria;

    public function setUp()
    {
        parent::setUp();

        $this->criteria = new Criteria('trololo');
    }

    protected function getQueryMock()
    {
        return new Query([
            'trololo' => [
                'foo' => [100, 300, 500], 'bar' => [200, 400, 600]
            ]
        ]);
    }

    protected function getEmptyQueryMock()
    {
        return new Query(['trololo' => []]);
    }

    public function testOneInsert()
    {
        $query = $this->getEmptyQueryMock();

        $this->criteria->write(['foo' => 1, 'bar' => 2]);

        $this->assertCount(1, $query->insert($this->criteria));
        $this->assertSame(['foo' => [1], 'bar' => [2]], $query->tables['trololo']);
    }

    public function testTwoInserts()
    {
        $query = $this->getEmptyQueryMock();

        $this->criteria->writePipe(['foo' => 1, 'bar' => 2]);
        $this->criteria->writePipe(['foo' => 3, 'bar' => 4]);

        $this->assertCount(2, $query->insert($this->criteria));
        $this->assertSame(['foo' => [1, 3], 'bar' => [2, 4]], $query->tables['trololo']);
    }

    public function testTwoInsertQueries()
    {
        $query = $this->getEmptyQueryMock();

        $this->criteria->write(['foo' => 1, 'bar' => 2]);
        $this->assertCount(1, $query->insert($this->criteria));
        $this->assertSame(['foo' => [1], 'bar' => [2]], $query->tables['trololo']);
        $this->criteria->write(['foo' => 1, 'bar' => 2]);
        $this->assertCount(1, $query->insert($this->criteria));
        $this->assertSame(['foo' => [1, 1], 'bar' => [2, 2]], $query->tables['trololo']);
    }

    /**
     * @expectedException \LinguaLeo\DataQuery\Exception\QueryException
     */
    public function testFailedRowsCountInsert()
    {
        $this->criteria->write(['foo' => [1, 3], 'bar' => 2]);

        $this->getEmptyQueryMock()->insert($this->criteria);
    }

    public function testDeleteAll()
    {
        $query = $this->getQueryMock();
        $this->assertCount(3, $query->delete($this->criteria));
        $this->assertEmpty($query->tables['trololo']);
    }

    /**
     * @expectedException \LinguaLeo\DataQuery\Exception\QueryException
     * @expectedExceptionMessage The "unknown" table not found
     */
    public function testNotFoundTable()
    {
        $this->getQueryMock()->select(new Criteria('unknown'));
    }

    /**
     * @expectedException \LinguaLeo\DataQuery\Exception\QueryException
     */
    public function testDeleteByUnknownColumn()
    {
        $this->criteria->where('ololo', 1);
        $this->getQueryMock()->delete($this->criteria);
    }

    public function testDeleteByOneColumn()
    {
        $query = $this->getQueryMock();
        $this->criteria->where('foo', 100);
        $this->assertCount(1, $query->delete($this->criteria));
        $this->assertSame(['foo' => [1 => 300, 500], 'bar' => [1 => 400, 600]], $query->tables['trololo']);
    }

    public function testDeleteByGreaterCondition()
    {
        $query = $this->getQueryMock();
        $this->criteria->where('foo', 100, Criteria::GREATER);
        $this->criteria->where('bar', 600, Criteria::LESS);
        $this->assertCount(1, $query->delete($this->criteria));
        $this->assertSame(['foo' => [0 => 100, 2 => 500], 'bar' => [0 => 200, 2 => 600]], $query->tables['trololo']);
    }

    public function testDeleteOnEmptyTable()
    {
        $query = $this->getEmptyQueryMock();
        $this->assertCount(0, $query->delete($this->criteria));
    }

    public function testUpdate()
    {
        $query = $this->getQueryMock();
        $this->criteria->write(['foo' => 1000, 'bar' => 2000]);
        $this->criteria->where('foo', 100);
        $this->assertCount(1, $query->update($this->criteria));
        $this->assertSame(['foo' => [1000, 300, 500], 'bar' => [2000, 400, 600]], $query->tables['trololo']);
    }

    /**
     * @expectedException \LinguaLeo\DataQuery\Exception\QueryException
     */
    public function testUpdateNoFields()
    {
        $this->getQueryMock()->update($this->criteria);
    }

    /**
     * @expectedException \LinguaLeo\DataQuery\Exception\QueryException
     */
    public function testUpdateUnknownFields()
    {
        $this->criteria->write(['ololo' => 1]);
        $this->getQueryMock()->update($this->criteria);
    }

    public function testUpdateNoAffectedRows()
    {
        $this->criteria->write(['bar' => 200]);
        $this->criteria->where('foo', 100);
        $this->assertCount(0, $this->getQueryMock()->update($this->criteria));
    }

    public function testIncrement()
    {
        $query = $this->getQueryMock();
        $this->criteria->write(['foo' => 1, 'bar' => -1]);
        $this->assertCount(3, $query->increment($this->criteria));
        $this->assertSame(['foo' => [101, 301, 501], 'bar' => [199, 399, 599]], $query->tables['trololo']);
    }

    public function testSelectColumn()
    {
        $this->assertSame([100, 300, 500], $this->getQueryMock()->select($this->criteria)->column(0));
    }

    public function testSelectKeyValue()
    {
        $this->assertSame(
            [100 => 200, 300 => 400, 500 => 600],
            $this->getQueryMock()->select($this->criteria)->keyValue()
        );
    }

    public function testSelectMany()
    {
        $this->assertSame(
            [
                ['foo' => 100, 'bar' => 200],
                ['foo' => 300, 'bar' => 400],
                ['foo' => 500, 'bar' => 600]
            ],
            $this->getQueryMock()->select($this->criteria)->many()
        );
    }

    public function testSelectOne()
    {
        $this->assertSame(
            ['foo' => 100, 'bar' => 200],
            $this->getQueryMock()->select($this->criteria)->one()
        );
    }

    public function testSelectValue()
    {
        $this->assertSame(100, $this->getQueryMock()->select($this->criteria)->value('foo'));
    }

    public function testSelectTable()
    {
        $this->assertSame(
            ['foo' => [100, 300, 500], 'bar' => [200, 400, 600]],
            $this->getQueryMock()->select($this->criteria)->table()
        );
    }

    public function testSelectMapping()
    {
        $this->criteria->read(['foo']);
        $this->assertSame(
            [
                ['foo' => 100],
                ['foo' => 300],
                ['foo' => 500]
            ],
            $this->getQueryMock()->select($this->criteria)->many()
        );
    }

    public function testSelectWithCondition()
    {
        $this->criteria->read(['foo']);
        $this->criteria->where('foo', 500, Criteria::NOT_EQUAL);
        $this->assertSame(
            [
                ['foo' => 100],
                ['foo' => 300]
            ],
            $this->getQueryMock()->select($this->criteria)->many()
        );
    }

    public function testSelectEmptyValue()
    {
        $this->criteria->where('foo', 1);
        $this->assertFalse($this->getQueryMock()->select($this->criteria)->value('foo'));
    }

    public function testSelectUndefinedColumn()
    {
        $this->assertNull($this->getQueryMock()->select($this->criteria)->value('ololo'));
    }

    public function provideConditions()
    {
        return [
            [1, Criteria::EQUAL, [1 => ['foo' => 1]]],
            [1, Criteria::NOT_EQUAL, [0 => ['foo' => null], 2 => ['foo' => 2]]],
            [1, Criteria::EQUAL_GREATER, [1 => ['foo' => 1], 2 => ['foo' => 2]]],
            [1, Criteria::EQUAL_LESS, [0 => ['foo' => null], 1 => ['foo' => 1]]],
            [1, Criteria::GREATER, [2 => ['foo' => 2]]],
            [1, Criteria::LESS, [0 => ['foo' => null]]],
            [null, Criteria::IS_NOT_NULL, [1 => ['foo' => 1], 2 => ['foo' => 2]]],
            [null, Criteria::IS_NULL, [0 => ['foo' => null]]],
            [[1,3], Criteria::IN, [1 => ['foo' => 1]]],
            [[1,3], Criteria::NOT_IN, [0 => ['foo' => null], 2 => ['foo' => 2]]]
        ];
    }

    /**
     * @dataProvider provideConditions
     */
    public function testSelectWithConditions($value, $comparison, $expected)
    {
        $query = new Query([
            'trololo' => [
                'foo' => [null, 1, 2]
            ]
        ]);
        $this->criteria->where('foo', $value, $comparison);
        $this->assertSame($expected, $query->select($this->criteria)->many());
    }

    /**
     * @expectedException \LinguaLeo\DataQuery\Exception\QueryException
     */
    public function testSelectWithUnknownCondition()
    {
        $this->criteria->where('foo', null, '#CUSTOM');
        $this->getQueryMock()->select($this->criteria);
    }

    public function testFreeResult()
    {
        $query = $this->getQueryMock();

        $this->criteria->where('foo', 100);
        $result = $query->select($this->criteria);
        $this->assertCount(1, $result);

        $result->free();
        $this->assertCount(0, $result);
    }
}