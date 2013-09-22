<?php

namespace LinguaLeo\MySQL;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Query
     */
    protected $query;

    public function setUp()
    {
        parent::setUp();

        $this->query = $this->getMock(
            '\LinguaLeo\MySQL\Query',
            ['executeQuery'],
            [new Pool(new Configuration(['test' => 'localhost'], 'test', 'test'))]
        );
    }

    private function assertSQL($query, $parameters = [])
    {
        $stmt = $this->getMock('\PDOStatement', ['rowCount', 'closeCursor']);

        $this->query
            ->expects($this->once())
            ->method('executeQuery')
            ->with($query, $parameters)
            ->will($this->returnValue($stmt));
    }

    public function testFindAll()
    {
        $this->assertSQL('SELECT * FROM test.trololo WHERE 1');

        $this->query
            ->table('trololo', 'test')
            ->select();
    }

    public function testFindAllFromTwoTables()
    {
        $this->assertSQL('SELECT * FROM test.foo, test.bar WHERE 1');

        $this->query
            ->table('foo', 'test')
            ->table('bar')
            ->select();
    }

    public function testFindAllWithColumns()
    {
        $this->assertSQL('SELECT foo, bar + 1 FROM test.trololo WHERE 1');

        $this->query
            ->table('trololo', 'test')
            ->select(['foo', 'bar + 1']);
    }

    public function testFindAllWithOnceWhere()
    {
        $this->assertSQL('SELECT * FROM test.trololo WHERE a = ?', [1]);

        $this->query
            ->table('trololo', 'test')
            ->where('a', 1)
            ->select();
    }

    public function testFindAllWithComplexWhere()
    {
        $this->assertSQL(
            'SELECT * FROM test.trololo WHERE a <> ? AND b > ? AND c < ? AND d >= ? AND e <= ?',
            [1, 2, 3, 4, 5]
        );

        $this->query
            ->table('trololo', 'test')
            ->where('a', 1, Query::NOT_EQUAL)
            ->where('b', 2, Query::GREATER)
            ->where('c', 3, Query::LESS)
            ->where('d', 4, Query::EQUAL_GREATER)
            ->where('e', 5, Query::EQUAL_LESS)
            ->select();
    }

    public function testFindAllWithInWhere()
    {
        $this->assertSQL(
            'SELECT * FROM test.trololo WHERE a IN (?,?,?)',
            [1, 2, 3]
        );

        $this->query
            ->table('trololo', 'test')
            ->where('a', [1, 2, 3], Query::IN)
            ->select();
    }

    public function testFindAllWithNotInWhere()
    {
        $this->assertSQL(
            'SELECT * FROM test.trololo WHERE a NOT IN (?,?,?)',
            [1, 2, 3]
        );

        $this->query
            ->table('trololo', 'test')
            ->where('a', [1, 2, 3], Query::NOT_IN)
            ->select();
    }

    public function testFindAllWithCompinedInEqualWhere()
    {
        $this->assertSQL(
            'SELECT * FROM test.trololo WHERE a IN (?,?,?) AND b = ?',
            [1, 2, 3, 4]
        );

        $this->query
            ->table('trololo', 'test')
            ->where('a', [1, 2, 3], Query::IN)
            ->where('b', 4)
            ->select();
    }

    public function testFindAllWithCustomWhere()
    {
        $this->assertSQL(
            'SELECT * FROM test.trololo WHERE a = b + (?)',
            [1]
        );

        $this->query
            ->table('trololo', 'test')
            ->where('a = b + (?)', 1, Query::CUSTOM)
            ->select();
    }

    public function testFindAllIsNull()
    {
        $this->assertSQL('SELECT * FROM test.trololo WHERE a IS NULL');

        $this->query
            ->table('trololo', 'test')
            ->where('a', null, Query::IS_NULL)
            ->select();
    }

    public function testFindAllIsNotNull()
    {
        $this->assertSQL('SELECT * FROM test.trololo WHERE a IS NOT NULL');

        $this->query
            ->table('trololo', 'test')
            ->where('a', null, Query::IS_NOT_NULL)
            ->select();
    }

    public function testExpressionInSelect()
    {
        $this->assertSQL('SELECT 1 + 1 FROM DUAL WHERE 1');

        $this->query->select(['1 + 1']);
    }

    public function testUpdateValues()
    {
        $this->assertSQL(
            'UPDATE test.trololo SET a = ? WHERE 1',
            [1]
        );

        $this->query
            ->table('trololo', 'test')
            ->update(['a' => 1]);
    }

    public function testIncrementValues()
    {
        $this->assertSQL(
            'UPDATE test.trololo SET a = a + 1, b = b + (?) WHERE c = ?',
            [-1, 2]
        );

        $this->query
            ->table('trololo', 'test')
            ->where('c', 2)
            ->increment(['a', 'b' => -1]);
    }

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\QueryException
     */
    public function testUpdateWithNoTablesDefinition()
    {
        $this->query->update(['a' => 1]);
    }

    public function testInsertRow()
    {
        $this->assertSQL('INSERT INTO test.trololo(foo,bar) VALUES (?,?)', [1, -2]);

        $this->query
            ->table('trololo', 'test')
            ->insert(['foo' => 1, 'bar' => -2]);
    }

    public function testInsertRowOnDuplicate()
    {
        $this->assertSQL(
            'INSERT INTO test.trololo(foo,bar) VALUES (?,?) ON DUPLICATE KEY UPDATE foo = VALUES(foo)',
            [1, -2]
        );


        $this->query
            ->table('trololo', 'test')
            ->insert(['foo' => 1, 'bar' => -2], 'foo');
    }

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\QueryException
     */
    public function testInsertWithNoTablesDefinition()
    {
        $this->query->insert(['a' => 1]);
    }

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\QueryException
     */
    public function testDeleteWithNoTablesDefinition()
    {
        $this->query->delete();
    }

    public function testDelete()
    {
        $this->assertSQL('DELETE FROM test.trololo WHERE 1');

        $this->query->table('trololo', 'test')->delete();
    }


    public function testDeleteWithCondition()
    {
        $this->assertSQL(
            'DELETE FROM test.trololo WHERE foo = ?',
            [1]
        );

        $this->query
            ->table('trololo', 'test')
            ->where('foo', 1)
            ->delete();
    }
}
