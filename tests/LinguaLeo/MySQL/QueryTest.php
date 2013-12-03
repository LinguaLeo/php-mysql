<?php

namespace LinguaLeo\MySQL;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Query
     */
    protected $query;

    /**
     * @var Criteria
     */
    protected $criteria;

    public function setUp()
    {
        parent::setUp();

        $this->query = $this->getMock(
            '\LinguaLeo\MySQL\Query',
            ['executeQuery'],
            [new Pool(new Configuration(['test' => 'localhost'], 'test', 'test'))]
        );

        $this->criteria = new Criteria('test', 'trololo');
    }

    private function assertSQL($query, $parameters = [])
    {
        $stmt = $this->getMock('\PDOStatement', ['rowCount', 'closeCursor']);

        $this->query
            ->expects($this->once())
            ->method('executeQuery')
            ->with('test', $query, $parameters)
            ->will($this->returnValue($stmt));
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
        $this->assertSQL('SELECT * FROM test.trololo WHERE 1 LIMIT 0,1');

        $this->criteria->limit(1);

        $this->query->select($this->criteria);
    }

    public function testFindWithLimitOffset()
    {
        $this->assertSQL('SELECT * FROM test.trololo WHERE 1 LIMIT 2,1');

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

    public function testFindAllWithCustomWhere()
    {
        $this->assertSQL(
            'SELECT * FROM test.trololo WHERE a = b + (?)',
            [1]
        );

        $this->criteria->where('a = b + (?)', 1, Criteria::CUSTOM);

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

    public function testUpdateValues()
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
            [
                function () {
                }
            ]
        ];
    }

    /**
     * @dataProvider provideNonScalarValue
     * @expectedException \LinguaLeo\MySQL\Exception\QueryException
     */
    public function testNonScalarValueInCondition($value)
    {
        $this->criteria->where('foo', $value);
        $this->query->select($this->criteria);
    }

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\QueryException
     */
    public function testUpdateWithNoWriteDefinition()
    {
        $this->query->update($this->criteria);
    }

    public function testInsertRow()
    {
        $this->assertSQL('INSERT INTO test.trololo(foo,bar) VALUES(?,?)', [1, -2]);

        $this->criteria->write(['foo' => 1, 'bar' => -2]);

        $this->query->insert($this->criteria);
    }

    public function testInsertRowOnDuplicate()
    {
        $this->assertSQL(
            'INSERT INTO test.trololo(foo,bar) VALUES(?,?) ON DUPLICATE KEY UPDATE foo=VALUES(foo)',
            [1, -2]
        );

        $this->criteria->write(['foo' => 1, 'bar' => -2]);

        $this->query->insert($this->criteria, 'foo');
    }

    public function testInsertRowOnDuplicateTwoColumns()
    {
        $this->assertSQL(
            'INSERT INTO test.trololo(foo,bar,baz) VALUES(?,?,?) ON DUPLICATE KEY UPDATE foo=VALUES(foo),baz=VALUES(baz)',
            [1, -2, 3]
        );

        $this->criteria->write(['foo' => 1, 'bar' => -2, 'baz' => 3]);

        $this->query->insert($this->criteria, ['foo', 'baz']);
    }

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\QueryException
     */
    public function testInsertWithNoWrtieDefinition()
    {
        $this->query->insert($this->criteria);
    }

    public function testMultiInsert()
    {
        $this->assertSQL(
            'INSERT INTO test.trololo(foo,bar,baz) VALUES(?,?,?),(?,?,?) ON DUPLICATE KEY UPDATE foo=VALUES(foo),baz=VALUES(baz)',
            [1, -2, 3, 4, 5, 6]
        );

        $criteriaOne = clone $this->criteria;
        $criteriaOne->write(['foo' => 1, 'bar' => -2, 'baz' => 3]);
        $criteriaTwo = clone $this->criteria;
        $criteriaTwo->write(['foo' => 4, 'bar' => 5, 'baz' => 6]);

        $this->query->multiInsert([$criteriaOne, $criteriaTwo], ['foo', 'baz']);
    }

    public function testMultiInsertWithout()
    {
        $this->assertSQL(
            'INSERT INTO test.trololo(foo,bar,baz) VALUES(?,?,?),(?,?,?)',
            [1, -2, 3, 4, 5, 6]
        );

        $criteriaOne = clone $this->criteria;
        $criteriaOne->write(['foo' => 1, 'bar' => -2, 'baz' => 3]);
        $criteriaTwo = clone $this->criteria;
        $criteriaTwo->write(['foo' => 4, 'bar' => 5, 'baz' => 6]);

        $this->query->multiInsert([$criteriaOne, $criteriaTwo]);
    }


    /**
     * @expectedException \LinguaLeo\MySQL\Exception\QueryException
     * @expectedExceptionMessage Criteria list cannot be empty
     */
    public function testMultiInsertEmptyException()
    {
        $this->query->multiInsert([], ['foo', 'baz']);
    }

    /**
     * @dataProvider dataProviderMultiInsertWrongClassException
     * @expectedException \LinguaLeo\MySQL\Exception\QueryException
     * @expectedExceptionMessage Criteria list must be array of Criteria
     */
    public function testMultiInsertWrongClassException($criteriaList)
    {
        $this->query->multiInsert($criteriaList, ['foo', 'baz']);
    }

    public function dataProviderMultiInsertWrongClassException()
    {
        $okCriteria = new Criteria('dbName', 'table');
        $okCriteria->write(['foo' => 4, 'bar' => 5, 'baz' => 6]);
        return [
            [[new \stdClass(), new \stdClass()]],
            [[$okCriteria, new \stdClass()]],
        ];
    }

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\QueryException
     * @expectedExceptionMessage No fields for insert statement
     */
    public function testMultiInsertEmptyFieldException()
    {
        $this->query->multiInsert([$this->criteria], ['foo', 'baz']);
    }

    /**
     * @dataProvider dataProviderMultiInsertDifferentCriteriaException
     * @expectedException \LinguaLeo\MySQL\Exception\QueryException
     * @expectedExceptionMessage Criteria list must have same from and field list
     */
    public function testMultiInsertDifferentCriteriaException($criteiaList)
    {
        $this->query->multiInsert($criteiaList, ['foo', 'baz']);
    }

    public function dataProviderMultiInsertDifferentCriteriaException()
    {
        $criteria1 = new Criteria('dbName', 'table');
        $criteria1->write(['foo' => 1, 'bar' => 2]);

        $criteria2 = clone $criteria1;
        $criteria2->write(['foo' => 3, 'baz' => 4]);

        $criteria3 = clone $criteria1;
        $criteria3->write(['foo' => 5, 'bar' => 6, 'baz' => 7]);

        $criteria4 = new Criteria($criteria1->dbName, 'tableOther');
        $criteria4->write(['foo' => 1, 'bar' => 2]);

        $criteria5 = new Criteria('dbNameOther', $criteria1->tableName);
        $criteria5->write(['foo' => 1, 'bar' => 2]);

        return [
            [[$criteria1, $criteria2]],
            [[$criteria1, $criteria3]],
            [[$criteria1, $criteria4]],
            [[$criteria1, $criteria5]]
        ];
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
}
