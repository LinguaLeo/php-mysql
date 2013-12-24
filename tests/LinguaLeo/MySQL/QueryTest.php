<?php

namespace LinguaLeo\MySQL;

use LinguaLeo\DataQuery\Criteria;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Query
     */
    protected $query;

    /*
     * @var Criteria
     */
    protected $criteria;

    public function setUp()
    {
        parent::setUp();

        $this->query = $this->getMock(
            '\LinguaLeo\MySQL\Query',
            ['executeQuery'],
            [
                new Pool(new Configuration(['test' => 'localhost'], 'test', 'test')),
                new Routing('test', ['trololo' => null])
            ]
        );

        $this->criteria = new Criteria('trololo');
    }

    private function assertSQL($query, $parameters = [])
    {
        $this->query
            ->expects($this->once())
            ->method('executeQuery')
            ->with($query, $parameters);
    }

    public function testFindAll()
    {
        $this->assertSQL('SELECT * FROM test.trololo WHERE 1');

        $this->query->select($this->criteria);
    }

    public function testFindAllWithColumns()
    {
        $this->assertSQL('SELECT foo,bar + 1 FROM test.trololo WHERE 1');

        $this->criteria->read(['foo', 'bar + 1']);

        $this->query->select($this->criteria);
    }

    public function testFindAllWithOnceWhere()
    {
        $this->assertSQL('SELECT * FROM test.trololo WHERE a=?', [1]);

        $this->criteria->where('a', 1);

        $this->query->select($this->criteria);
    }

    public function testFindWithLimit()
    {
        $this->assertSQL('SELECT * FROM test.trololo WHERE 1 LIMIT 1');

        $this->criteria->limit(1);

        $this->query->select($this->criteria);
    }

    public function testFindWithLimitOffset()
    {
        $this->assertSQL('SELECT * FROM test.trololo WHERE 1 LIMIT 1 OFFSET 2');

        $this->criteria->limit(1, 2);

        $this->query->select($this->criteria);
    }

    public function testFindAllWithComplexWhere()
    {
        $this->assertSQL(
            'SELECT * FROM test.trololo WHERE a<>? AND b>? AND c<? AND d>=? AND e<=?',
            [1, 2, 3, 4, 5]
        );

        $this->criteria->where('a', 1, Criteria::NOT_EQUAL);
        $this->criteria->where('b', 2, Criteria::GREATER);
        $this->criteria->where('c', 3, Criteria::LESS);
        $this->criteria->where('d', 4, Criteria::EQUAL_GREATER);
        $this->criteria->where('e', 5, Criteria::EQUAL_LESS);

        $this->query->select($this->criteria);
    }

    public function testFindAllWithInWhere()
    {
        $this->assertSQL(
            'SELECT * FROM test.trololo WHERE a IN(?,?,?)',
            [1, 2, 3]
        );

        $this->criteria->where('a', [1, 2, 3], Criteria::IN);

        $this->query->select($this->criteria);
    }

    public function testFindAllWithNotInWhere()
    {
        $this->assertSQL(
            'SELECT * FROM test.trololo WHERE a NOT IN(?,?,?)',
            [1, 2, 3]
        );

        $this->criteria->where('a', [1, 2, 3], Criteria::NOT_IN);

        $this->query->select($this->criteria);
    }

    public function testFindAllWithCompinedInEqualWhere()
    {
        $this->assertSQL(
            'SELECT * FROM test.trololo WHERE a IN(?,?,?) AND b=?',
            [1, 2, 3, 4]
        );

        $this->criteria->where('a', [1, 2, 3], Criteria::IN);
        $this->criteria->where('b', 4);

        $this->query->select($this->criteria);
    }

    public function testFindAllIsNull()
    {
        $this->assertSQL('SELECT * FROM test.trololo WHERE a IS NULL');

        $this->criteria->where('a', null, Criteria::IS_NULL);

        $this->query->select($this->criteria);
    }

    public function testFindAllIsNotNull()
    {
        $this->assertSQL('SELECT * FROM test.trololo WHERE a IS NOT NULL');

        $this->criteria->where('a', null, Criteria::IS_NOT_NULL);

        $this->query->select($this->criteria);
    }

    public function testSelectWithMultiOrder()
    {
        $this->assertSQL('SELECT * FROM test.trololo WHERE 1 ORDER BY foo ASC, bar DESC');

        $this->criteria->orderBy('foo');
        $this->criteria->orderBy('bar', SORT_DESC);

        $this->query->select($this->criteria);
    }

    public function testSelectWithOrderAndLimit()
    {
        $this->assertSQL('SELECT * FROM test.trololo WHERE 1 ORDER BY foo ASC LIMIT 100');

        $this->criteria->limit(100);
        $this->criteria->orderBy('foo', SORT_ASC);

        $this->query->select($this->criteria);
    }

    /**
     * @expectedException \LinguaLeo\DataQuery\Exception\QueryException
     */
    public function testUnknownOrderType()
    {
        $this->criteria->orderBy('foo', SORT_NATURAL);
        $this->query->select($this->criteria);
    }

    public function testUpdateValue()
    {
        $this->assertSQL(
            'UPDATE test.trololo SET a=? WHERE 1',
            [1]
        );

        $this->criteria->write(['a' => 1]);

        $this->query->update($this->criteria);
    }

    public function testIncrementValues()
    {
        $this->assertSQL(
            'UPDATE test.trololo SET a=a+(?),b=b+(?) WHERE c=?',
            [1, -1, 2]
        );

        $this->criteria->write(['a' => 1, 'b' => -1]);
        $this->criteria->where('c', 2);

        $this->query->increment($this->criteria);
    }

    public function provideNonScalarValue()
    {
        return [
            [[]],
            [new \stdClass()],
            [function () {}]
        ];
    }

    /**
     * @dataProvider provideNonScalarValue
     * @expectedException \LinguaLeo\DataQuery\Exception\QueryException
     */
    public function testNonScalarValueInCondition($value)
    {
        $this->criteria->where('foo', $value);
        $this->query->select($this->criteria);
    }

    /**
     * @expectedException \LinguaLeo\DataQuery\Exception\QueryException
     */
    public function testUpdateWithNoWriteDefinition()
    {
        $this->query->update($this->criteria);
    }

    public function testInsertRow()
    {
        $this->assertSQL('INSERT INTO test.trololo(foo,bar) VALUES (?,?)', [1, -2]);
        $this->criteria->write(['foo' => 1, 'bar' => -2]);
        $this->query->insert($this->criteria);
    }

    public function testMultiInsertRow()
    {
        $this->assertSQL('INSERT INTO test.trololo(foo,bar) VALUES (?,?),(?,?)', [1, -2, 2, 3]);

        $this->criteria->write(['foo' => [1, 2], 'bar' => [-2, 3]]);

        $this->query->insert($this->criteria);
    }

    /**
     * @expectedException \LinguaLeo\DataQuery\Exception\QueryException
     */
    public function testWrongValuesCountForMultiInsertRow()
    {
        $this->criteria->write(['foo' => [1, 2], 'bar' => -2]);

        $this->query->insert($this->criteria);
    }

    public function testInsertRowOnDuplicateTwoColumns()
    {
        $this->assertSQL(
            'INSERT INTO test.trololo(foo,bar,baz) VALUES (?,?,?) ON DUPLICATE KEY UPDATE foo=VALUES(foo),baz=VALUES(baz)',
            [1, -2, 3]
        );

        $this->criteria->write(['foo' => 1, 'bar' => -2, 'baz' => 3]);
        $this->criteria->upsert(['foo', 'baz']);

        $this->query->insert($this->criteria);
    }

    /**
     * @expectedException \LinguaLeo\DataQuery\Exception\QueryException
     */
    public function testInsertWithNoWrtieDefinition()
    {
        $this->query->insert($this->criteria);
    }

    public function testDelete()
    {
        $this->assertSQL('DELETE FROM test.trololo WHERE 1');

        $this->query->delete($this->criteria);
    }

    public function testDeleteWithCondition()
    {
        $this->assertSQL(
            'DELETE FROM test.trololo WHERE foo=?',
            [1]
        );

        $this->criteria->where('foo', 1);

        $this->query->delete($this->criteria);
    }

    public function testAggregate()
    {
        $this->assertSQL(
            'SELECT COUNT(*),SUM(bar),baz FROM test.trololo WHERE foo=?',
            [1]
        );

        $this->criteria->read(['*' => 'count', 'bar' => 'sum', 'baz']);
        $this->criteria->where('foo', 1);

        $this->query->select($this->criteria);
    }
}
