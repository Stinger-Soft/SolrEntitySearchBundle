<?php
declare(strict_types=1);

namespace Tests\Services;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Paginator;
use StingerSoft\EntitySearchBundle\Model\Query;
use StingerSoft\EntitySearchBundle\Tests\AbstractORMTestCase;
use StingerSoft\EntitySearchBundle\Tests\Fixtures\ORM\Beer;
use StingerSoft\EntitySearchBundle\Tests\Fixtures\ORM\Car;
use StingerSoft\SolrEntitySearchBundle\Services\SearchService;

class SearchServiceRealTest extends AbstractORMTestCase {

	protected $indexCount = 0;

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	public function setUp() {
		parent::setUp();
		$this->getMockSqliteEntityManager();
		$this->indexCount = 0;
		$this->getSearchService($this->em)->clearIndex();
		$this->assertEquals(0, $this->getSearchService($this->em)->getIndexSize());
	}

	public function testAddField() {
		$service = $this->getSearchService();
		$service->addField('test', 'string');
	}

	public function testSaveDocument() {
		$service = $this->getSearchService();
		$this->indexBeer($service);
		$service->clearIndex();
		$this->assertEquals(0, $service->getIndexSize());
	}

	public function testSaveDocumentComposite() {
		$car = new Car('S500', 2016);
		$this->em->persist($car);
		$this->em->flush();

		$service = $this->getSearchService();
		$document = $service->createEmptyDocumentFromEntity($car);
		$this->assertEquals(0, $service->getIndexSize());
		$service->saveDocument($document);
		$this->em->flush();

		$this->assertEquals(1, $service->getIndexSize());

		$service->clearIndex();
		$this->assertEquals(0, $service->getIndexSize());
	}

	public function testRemoveDocument() {
		$service = $this->getSearchService();
		$result = $this->indexBeer($service);

		$service->removeDocument($result[1]);
		$this->em->flush();
		$this->assertEquals(0, $service->getIndexSize());
	}

	public function testAutocompletion() {
		$service = $this->getSearchService();
		$result = $this->indexBeer($service);

		$suggests = $service->autocomplete('He');
		$this->assertCount(1, $suggests);
		$this->assertContains($result[0]->getTitle(), $suggests);
	}

	public function testSearch() {
		$service = $this->getSearchService();
		$this->indexBeer($service);
		$this->indexBeer($service, 'Haake Beck');
		$this->indexBeer($service, 'Haake Beck');
		$this->indexBeer($service, 'Haake Beck Kräusen');

		$query = new Query('Beck', array(), array(
			\StingerSoft\EntitySearchBundle\Model\Document::FIELD_TITLE,
			\StingerSoft\EntitySearchBundle\Model\Document::FIELD_TYPE
		));

		$result = $service->search($query);
		$this->assertCount(3, $result->getResults());

		/**
		 *
		 * @var FacetSetAdapter $facets
		 */
		$facets = $result->getFacets();
		$titleFacets = $facets->getFacet(\StingerSoft\EntitySearchBundle\Model\Document::FIELD_TITLE);
		$this->assertCount(3, $titleFacets);
		$this->assertArrayHasKey('Haake Beck', $titleFacets);
		$this->assertArrayHasKey('Haake Beck Kräusen', $titleFacets);
		$this->assertEquals($titleFacets['Haake Beck']['count'], 2);
		$this->assertEquals($titleFacets['Haake Beck Kräusen']['count'], 1);
		$typeFacets = $facets->getFacet(\StingerSoft\EntitySearchBundle\Model\Document::FIELD_TYPE);
		$this->assertCount(1, $typeFacets);

	}

	public function testSearchCorrection() {
		$service = $this->getSearchService();
		$this->indexBeer($service);
		$this->indexBeer($service, 'Haake Beck');
		$this->indexBeer($service, 'Haake Beck Beer');
		$this->indexBeer($service, 'Haake Beck Kräusen');

		$query = new Query('Hake Bcek', array(), array(
			\StingerSoft\EntitySearchBundle\Model\Document::FIELD_TITLE,
			\StingerSoft\EntitySearchBundle\Model\Document::FIELD_TYPE
		));

		$result = $service->search($query);
		$this->assertCount(0, $result->getResults());

		$this->assertGreaterThan(0, count($result->getCorrections()));
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Tests\AbstractTestCase::getUsedEntityFixtures()
	 */
	protected function getUsedEntityFixtures(): array {
		return array(
			Beer::class,
			Car::class
		);
	}

	/**
	 *
	 * @return \StingerSoft\SolrEntitySearchBundle\Services\SearchService
	 */
	protected function getSearchService(EntityManagerInterface $em = null) {
		$service = new SearchService(new Paginator(), array(
			'path'      => '/solr/platform/',
			'port'      => '8983',
			'ipaddress' => '127.0.0.1'
		));
		$service->setObjectManager($em ?? $this->em);
		if(!$service->ping()) {
			self::markTestSkipped('No Solr instance found');
		}
		return $service;
	}

	protected function indexBeer(SearchService $service, $title = 'Hemelinger') {
		$beer = new Beer();
		$beer->setTitle($title);
		$this->em->persist($beer);
		$this->em->flush();

		$document = $service->createEmptyDocumentFromEntity($beer);
		$this->assertEquals($this->indexCount, $service->getIndexSize());
		$beer->indexEntity($document);
		$service->saveDocument($document);
		$this->em->flush();
		$this->assertEquals(++$this->indexCount, $service->getIndexSize());
		return array(
			$beer,
			$document
		);
	}
}

