<?php

namespace LinguaLeo\MySQL;

class RoutingTest extends \PHPUnit_Framework_TestCase
{
    protected $routing;

    public function setUp()
    {
        parent::setUp();

        $this->routing = new Routing('linguadb',
            [
                'user' => null,
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
                ]
            ]
        );
    }

    public function provideRouteArguments()
    {
        return [
            ['user', ['spot_id' => 3, 'chunk_id' => 99, 'locale' => 'ru'], ['linguadb','user']],
            ['translate', ['locale' => 'ru'], ['lang_ru','translate']],
            ['word_user', ['spot_id' => 3, 'chunk_id' => 99, 'locale' => 'ru'], ['leotestdb_i18n_3','word_user_99']],
            ['word_user', ['spot_id' => 3, 'chunk_id' => 99, 'locale' => 'pt'], ['pt_leotestdb_i18n_3','word_user_99']],
            ['server_node', ['spot_id' => 3, 'chunk_id' => 99, 'locale' => 'pt'], ['linguadb','server_node_99']],
            ['word', ['spot_id' => 3, 'chunk_id' => 99, 'locale' => 'pt'], ['test_3_pt','word']],
            ['content', ['spot_id' => 'c4ca42', 'chunk_id' => 99, 'locale' => 'ru'], ['c4ca42_linguadb','content']],
        ];
    }

    /**
     * @dataProvider provideRouteArguments
     */
    public function testRouteGetter($tableName, $arguments, $expected)
    {
        $this->routing->setArguments($arguments);
        $this->assertSame($expected, $this->routing->getRoute($tableName));
    }

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\RoutingException
     * @expectedExceptionMessage Unknown "ololo" table name
     */
    public function testUnknownTableName()
    {
        $this->routing->getRoute('ololo');
    }

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\RoutingException
     * @expectedExceptionMessage Unknown "qaz" option type for "atata" route
     */
    public function testUnknownOptionType()
    {
        $routing = new Routing('ololo', ['atata' => ['options' => 'qaz']]);
        $routing->getRoute('atata');
    }

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\RoutingException
     * @expectedExceptionMessage Unknown "qaz" constant for "as" operator
     */
    public function testUnknownAsOperator()
    {
        $routing = new Routing('ololo', ['atata' => ['options' => ['chunked' => ['as' => 'qaz']]]]);
        $routing->setArguments(['chunk_id' => 1]);
        $routing->getRoute('atata');
    }

    /**
     * @expectedException \LinguaLeo\MySQL\Exception\RoutingException
     * @expectedExceptionMessage The "chunk_id" parameter is required
     */
    public function testRequiredParameter()
    {
        $routing = new Routing('ololo', ['atata' => ['options' => ['chunked']]]);
        $routing->getRoute('atata');
    }
}