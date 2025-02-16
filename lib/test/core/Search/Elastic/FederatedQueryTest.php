<?php

class Search_Elastic_FederatedQueryTest extends PHPUnit\Framework\TestCase
{
    public $indexC;
    private $indexA;

    protected function setUp(): void
    {
        $elasticSearchHost = empty(getenv('ELASTICSEARCH_HOST')) ? 'localhost' : getenv('ELASTICSEARCH_HOST');
        $connection = Search_Elastic_Connection::build('http://' . $elasticSearchHost . ':9200');

        $status = $connection->getStatus();
        if (! $status->ok) {
            $this->markTestSkipped('Elasticsearch needs to be available on ' . $elasticSearchHost . ':9200 for the test to run.');
        }

        $this->indexA = new Search_Elastic_Index($connection, 'test_index_a');
        $this->indexA->destroy();
        $factory = $this->indexA->getTypeFactory();
        $this->indexA->addDocument([
            'object_type' => $factory->identifier('wiki page'),
            'object_id' => $factory->identifier('PageA'),
            'contents' => $factory->plaintext('Hello World A'),
            'url' => $factory->identifier('PageA'),
        ]);

        $indexB = new Search_Elastic_Index($connection, 'test_index_b_foo');
        $indexB->destroy();
        $factory = $indexB->getTypeFactory();
        $indexB->addDocument([
            'object_type' => $factory->identifier('wiki page'),
            'object_id' => $factory->identifier('PageB'),
            'contents' => $factory->plaintext('Hello World B'),
            'url' => $factory->identifier('PageB'),
        ]);

        $this->indexC = new Search_Elastic_Index($connection, 'test_index_c');
        $this->indexC->destroy();
        $factory = $this->indexC->getTypeFactory();
        $this->indexC->addDocument([
            'object_type' => $factory->identifier('wiki page'),
            'object_id' => $factory->identifier('PageB'),
            'contents' => $factory->plaintext('Hello World C'),
            'url' => $factory->identifier('/PageC'),
        ]);

        $connection->refresh('*');
        $connection->assignAlias('test_index_b', 'test_index_b_foo');
    }

    public function testSearchAffectsAllForeign()
    {
        $query = new Search_Query('hello');
        $sub = new Search_Query('hello');

        $query->includeForeign('test_index_b', $sub);
        $query->includeForeign('test_index_c', $sub);
        $result = $query->search($this->indexA);

        $this->assertCount(3, $result);
    }

    public function testResultsIndicateOriginIndex()
    {
        $query = new Search_Query('foobar');
        $sub = new Search_Query('C');

        $query->includeForeign('test_index_b', $sub);
        $query->includeForeign('test_index_c', $sub);
        $result = $query->search($this->indexA);

        $first = $result[0];
        $this->assertEquals('test_index_c', $first['_index']);
    }

    public function testUnexpandAliases()
    {
        $query = new Search_Query('foobar');
        $sub = new Search_Query('B');

        $query->includeForeign('test_index_b', $sub);
        $query->includeForeign('test_index_c', $sub);
        $result = $query->search($this->indexA);

        $first = $result[0];
        // Note : test_index_b is an alias to test_index_b_...
        $this->assertEquals('test_index_b', $first['_index']);
    }

    public function testTransformsApplyPerIndex()
    {
        $query = new Search_Query('Hello');
        $query->applyTransform(new Search\Federated\UrlPrefixTransform('http://foo.example.com'));
        $sub = new Search_Query('Hello');
        $sub->applyTransform(new Search\Federated\UrlPrefixTransform('http://bar.example.com/'));

        $query->includeForeign('test_index_c', $sub);
        $result = $query->search($this->indexA);

        $urls = [$result[0]['url'], $result[1]['url']];

        $this->assertContains('http://foo.example.com/PageA', $urls);
        $this->assertContains('http://bar.example.com/PageC', $urls);
    }
}
