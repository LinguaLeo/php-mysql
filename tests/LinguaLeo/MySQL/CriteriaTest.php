<?php

namespace LinguaLeo\MySQL;

class CriteriaTest extends \PHPUnit_Framework_TestCase
{
    protected $criteria;

    public function setUp()
    {
        $this->criteria = new Criteria('foo', 'bar');
    }

    public function testWhere()
    {
        $this->criteria->where('baz', 1, Criteria::GREATER);
        $this->assertSame([['baz', 1, Criteria::GREATER]], $this->criteria->conditions);
    }

    public function testWhereMany()
    {
        $this->criteria
            ->where('baz', 1, Criteria::GREATER)
            ->where('quux', 2, Criteria::NOT_EQUAL);
        $this->assertSame([
            ['baz', 1, Criteria::GREATER],
            ['quux', 2, Criteria::NOT_EQUAL],
        ], $this->criteria->conditions);
    }

    public function testLimit()
    {
        $this->criteria->limit(1);
        $this->assertSame(1, $this->criteria->limit);
        $this->assertSame(0, $this->criteria->offset);
    }

    public function testLimitOffset()
    {
        $this->criteria->limit(1, 2);
        $this->assertSame(1, $this->criteria->limit);
        $this->assertSame(2, $this->criteria->offset);
    }

    public function testRead()
    {
        $this->criteria->read(['a', 'b']);
        $this->assertSame(['a', 'b'], $this->criteria->fields);
    }

    public function testReadReset()
    {
        $this->criteria->read(['a', 'b']);
        $this->criteria->read(['c', 'd']);
        $this->assertSame(['c', 'd'], $this->criteria->fields);
    }

    public function testWrite()
    {
        $this->criteria->write(['a' => 1, 'b' => 2]);
        $this->assertSame(['a', 'b'], $this->criteria->fields);
        $this->assertSame([1, 2], $this->criteria->values);
    }

    public function testWriteReset()
    {
        $this->criteria->write(['a' => 1, 'b' => 2]);
        $this->criteria->write(['c' => 3, 'd' => 4]);
        $this->assertSame(['c', 'd'], $this->criteria->fields);
        $this->assertSame([3, 4], $this->criteria->values);
    }

    public function testAppendOne()
    {
        $this->criteria->append(['a' => 1, 'b' => 2]);
        $this->assertSame(['a', 'b'], $this->criteria->fields);
        $this->assertSame([1, 2], $this->criteria->values);
    }

    public function testAppendMany()
    {
        $this->criteria
            ->append(['a' => 1, 'b' => 2])
            ->append(['a' => 3, 'b' => 4])
            ->append(['a' => 5, 'b' => 6]);

        $this->assertSame(['a', 'b'], $this->criteria->fields);
        $this->assertSame([[1,3,5], [2,4,6]], $this->criteria->values);
    }

    public function testAppendDefinedFields()
    {
        $this->criteria
            ->append(['a' => 1, 'b' => 2])
            ->append(['a' => 3, 'b' => 4, 'c' => 3]);

        $this->assertSame(['a', 'b'], $this->criteria->fields);
        $this->assertSame([[1,3], [2,4]], $this->criteria->values);
    }

    public function testAppendDefinedFieldsAsNullable()
    {
        $this->criteria
            ->append(['a' => 1, 'b' => 2])
            ->append(['a' => 3, 'b' => null]);

        $this->assertSame(['a', 'b'], $this->criteria->fields);
        $this->assertSame([[1,3], [2,null]], $this->criteria->values);
    }

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\CriteriaException
     */
    public function testAppendUndefinedFields()
    {
        $this->criteria
            ->append(['a' => 1, 'b' => 2, 'c' => 3])
            ->append(['a' => 4, 'b' => 5]);
    }
}