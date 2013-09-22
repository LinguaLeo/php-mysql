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

$query
    ->table('table_name1', 'db_name1')
    ->insert(['foo' => 1, 'bar' => 2]);

// INSERT INTO db_name1.table_name1(foo,bar) VALUES (1,2) ON DUPLICATE KEY UPDATE foo = VALUES(foo)

$query
    ->table('table_name1', 'db_name1')
    ->insert(['foo' => 1, 'bar' => 2], 'foo');
```

Query: Update
-------------

```php
$query = new LinguaLeo\MySQL\Query($pool);

// UPDATE db_name1.table_name1 SET foo = 1 WHERE bar = 2

$query
    ->table('table_name1', 'db_name1')
    ->where('bar', 2)
    ->update(['foo' => 1]);
```

Query: Increment
----------------

```php
$query = new LinguaLeo\MySQL\Query($pool);

// UPDATE db_name1.table_name1 SET foo = foo + 1, bar = bar - 1 WHERE 1

$query
    ->table('table_name1', 'db_name1')
    ->increment(['foo', 'bar' => -1]);
```

Query: Delete
-------------

```php
$query = new LinguaLeo\MySQL\Query($pool);

// DELETE FROM db_name1.table_name1 WHERE bar = 1 AND foo > 1

$query
    ->table('table_name1', 'db_name1')
    ->where('bar', 1)
    ->where('foo', 1, LinguaLeo\MySQL\Query::GREATER)
    ->delete();
```

Query: Select
-------------

```php
$query = new LinguaLeo\MySQL\Query($pool);

// SELECT * FROM db_name1.table_name1

$query
    ->table('table_name1', 'db_name1')
    ->select();

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
// AND  k & 8 = 0

$query
    ->table('table_name1', 'db_name1')
    ->where('a', 1)
    ->where('b', 1, LinguaLeo\MySQL\Query::EQUAL_GREATER)
    ->where('c', 1, LinguaLeo\MySQL\Query::EQUAL_LESS)
    ->where('d', 1, LinguaLeo\MySQL\Query::NOT_EQUAL)
    ->where('e', 1, LinguaLeo\MySQL\Query::GREATER)
    ->where('f', 1, LinguaLeo\MySQL\Query::LESS)
    ->where('g', [1,2], LinguaLeo\MySQL\Query::IN)
    ->where('h', [1,2], LinguaLeo\MySQL\Query::NOT_IN)
    ->where('i', null, LinguaLeo\MySQL\Query::IS_NULL)
    ->where('j', null, LinguaLeo\MySQL\Query::IS_NOT_NULL)
    ->where('k & ? = 0', 8, LinguaLeo\MySQL\Query::CUSTOM)
    ->select(['a', 'b']);
```