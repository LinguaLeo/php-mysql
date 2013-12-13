LinguaLeo MySQL micro framework
===============================

Connection Pooling
------------------

```php
$connect_map = [
    'db_name1' => 'hostname1',
    'db_name2' => 'hostname2',
    'db_name3' => 'hostname2'
];

// prepare the configuration
$config = new LinguaLeo\MySQL\Configuration($connect_map, 'root', 'passwd');

// instantiate the pool
$pool = new LinguaLeo\MySQL\Pool($config);

// how to connect?
$pool->connect('db_name1');
$pool->connect('db_name2');
$pool->connect('db_name3');
```

Query: Insert
-------------

```php
$query = new LinguaLeo\MySQL\Query($pool);

// INSERT INTO db_name1.table_name1(foo,bar) VALUES (1,2)

$criteria = new LinguaLeo\DataQuery\Criteria('db_name1', 'table_name1');
$criteria->write(['foo' => 1, 'bar' => 2]);

$query->insert($criteria);
```

Query: Upsert
-------------

```php
$query = new LinguaLeo\MySQL\Query($pool);

// INSERT INTO db_name1.table_name1(foo,bar) VALUES (1,2) ON DUPLICATE KEY UPDATE foo = VALUES(foo)

$criteria = new LinguaLeo\DataQuery\Criteria('db_name1', 'table_name1');
$criteria->write(['foo' => 1, 'bar' => 2]);
$criteria->upsert(['foo']);

$query->insert($criteria);
```

Query: Insert (multi)
---------------------

```php
$query = new LinguaLeo\MySQL\Query($pool);

// INSERT INTO db_name1.table_name1(foo,bar) VALUES (1,2),(3,4)

$criteria = new LinguaLeo\DataQuery\Criteria('db_name1', 'table_name1');

// variant 1
$criteria->write(['foo' => [1,3], 'bar' => [2,4]]);

// or variant 2
$criteria->writePipe(['foo' => 1, 'bar' => 2]);
$criteria->writePipe(['foo' => 3, 'bar' => 4]);

$query->insert($criteria);
```

Query: Update
-------------

```php
$query = new LinguaLeo\MySQL\Query($pool);

// UPDATE db_name1.table_name1 SET foo = 1 WHERE bar = 2

$criteria = new LinguaLeo\DataQuery\Criteria('db_name1', 'table_name1');

$criteria->where('bar', 2);
$criteria->write(['foo' => 1]);

$query->update($criteria);
```

Query: Increment
----------------

```php
$query = new LinguaLeo\MySQL\Query($pool);

// UPDATE db_name1.table_name1 SET foo = foo + 1, bar = bar - 1 WHERE 1

$criteria = new LinguaLeo\DataQuery\Criteria('db_name1', 'table_name1');
$criteria->write(['foo' => 1, 'bar' => -1]);

$query->increment($criteria);
```

Query: Delete
-------------

```php
$query = new LinguaLeo\MySQL\Query($pool);

// DELETE FROM db_name1.table_name1 WHERE bar = 1 AND foo > 1

$criteria = new LinguaLeo\DataQuery\Criteria('db_name1', 'table_name1');

$criteria->where('bar', 1);
$criteria->where('foo', 1, LinguaLeo\DataQuery\Criteria::GREATER)

$query->delete($criteria);
```

Query: Select
-------------

```php
$query = new LinguaLeo\MySQL\Query($pool);

// SELECT * FROM db_name1.table_name1

$criteria = new LinguaLeo\DataQuery\Criteria('db_name1', 'table_name1');

$query->select($criteria);

// SELECT a,b FROM db_name1.table_name1
// WHERE
//      a = 1
// AND  b >= 1
// AND  c <= 1
// AND  d <> 1
// AND  e > 1
// AND  f < 1
// AND  g IN (1,2)
// AND  h NOT IN (1,2)
// AND  i IS NULL
// AND  j IS NOT NULL

$criteria
    ->read(['a', 'b'])
    ->where('a', 1)
    ->where('b', 1, LinguaLeo\DataQuery\Criteria::EQUAL_GREATER)
    ->where('c', 1, LinguaLeo\DataQuery\Criteria::EQUAL_LESS)
    ->where('d', 1, LinguaLeo\DataQuery\Criteria::NOT_EQUAL)
    ->where('e', 1, LinguaLeo\DataQuery\Criteria::GREATER)
    ->where('f', 1, LinguaLeo\DataQuery\Criteria::LESS)
    ->where('g', [1,2], LinguaLeo\DataQuery\Criteria::IN)
    ->where('h', [1,2], LinguaLeo\DataQuery\Criteria::NOT_IN)
    ->where('i', null, LinguaLeo\DataQuery\Criteria::IS_NULL)
    ->where('j', null, LinguaLeo\DataQuery\Criteria::IS_NOT_NULL);

$query->select($criteria);
```

Query: Select (aggregation)
---------------------------

```php
$query = new LinguaLeo\MySQL\Query($pool);

// SELECT COUNT(*), SUM(foo) FROM db_name1.table_name1

$criteria = new LinguaLeo\DataQuery\Criteria('db_name1', 'table_name1');

$criteria->read(['*' => 'count', 'foo' => 'sum']);

$query->select($criteria);
```