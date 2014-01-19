<?php

namespace LinguaLeo\MySQL;

use LinguaLeo\DataQuery\Criteria;

class RoutingTest extends \PHPUnit_Framework_TestCase
{
    private static $tablesMap = [
        'translate' => [
            'db' => 'lang',
            'options' => ['localized' => ['as' => 'suff']]
        ],
        'word_user' => [
            'db' => 'leotestdb_i18n',
            'options' => ['chunked', 'spotted', 'localized' => ['not' => ['ru'], 'as' => 'pref']],
        ],
        'server_node' => [
            'options' => 'chunked'
        ],
        'word' => [
            'db' => 'test',
            'options' => ['spotted', 'localized']
        ],
        'content' => [
            'options' => ['spotted' => ['as' => 'pref'], 'chunked' => ['not' => 99]]
        ],
        'word_set' => [
            'table_name' => 'glossary'
        ]
    ];

    public function provideRouteArguments()
    {
        return [
            ['user', ['spot_id' => 3, 'chunk_id' => 99, 'locale' => 'ru'], 'linguadb.user'],
            ['translate', ['locale' => 'ru'], 'lang_ru.translate'],
            ['word_user', ['spot_id' => 3, 'chunk_id' => 99, 'locale' => 'ru'], 'leotestdb_i18n_3.word_user_99'],
            ['word_user', ['spot_id' => 3, 'chunk_id' => 99, 'locale' => 'pt'], 'pt_leotestdb_i18n_3.word_user_99'],
            ['server_node', ['spot_id' => 3, 'chunk_id' => 99, 'locale' => 'pt'], 'linguadb.server_node_99'],
            ['word', ['spot_id' => 3, 'chunk_id' => 99, 'locale' => 'pt'], 'test_3_pt.word'],
            ['content', ['spot_id' => 'c4ca42', 'chunk_id' => 99, 'locale' => 'ru'], 'c4ca42_linguadb.content'],
            ['word_set', ['locale' => 'ru'], 'linguadb.glossary']
        ];
    }

    /**
     * @dataProvider provideRouteArguments
     */
    public function testRouteGetter($tableName, $arguments, $expected)
    {
        $routing = new Routing('linguadb', self::$tablesMap);
        $this->assertSame($expected, (string) $routing->getRoute(new Criteria($tableName, $arguments)));
    }

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\RoutingException
     * @expectedExceptionMessage Unknown "qaz" option type
     */
    public function testUnknownOptionType()
    {
        $routing = new Routing('ololo', ['atata' => ['options' => 'qaz']]);
        $routing->getRoute(new Criteria('atata'));
    }

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\RoutingException
     * @expectedExceptionMessage Unknown "qaz" constant for "as" operator
     */
    public function testUnknownAsOperator()
    {
        $routing = new Routing('ololo', ['atata' => ['options' => ['chunked' => ['as' => 'qaz']]]]);
        $routing->getRoute(new Criteria('atata', ['chunk_id' => 1]));
    }
}