<?php
declare(strict_types=1);

/*
 * This file is part of the Stinger Entity Search package.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StingerSoft\SolrEntitySearchBundle\Model;

use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Solarium\Client;
use Solarium\Component\Result\Highlighting\Highlighting;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Result;
use StingerSoft\EntitySearchBundle\Model\Document;
use StingerSoft\EntitySearchBundle\Model\PaginatableResultSet;
use StingerSoft\EntitySearchBundle\Model\Result\Correction;
use StingerSoft\EntitySearchBundle\Model\ResultSetAdapter;

class KnpResultSet extends ResultSetAdapter implements PaginatableResultSet {

	/**
	 *
	 * @var Query
	 */
	protected Query $query;

	/**
	 *
	 * @var string
	 */
	protected string $term;

	/**
	 *
	 * @var Client
	 */
	protected Client $client;

	/**
	 *
	 * @var SlidingPagination|Document[]
	 */
	protected $lastResult = null;

	/**
	 *
	 * @var Result|null
	 */
	protected ?Result $lastSolrResult = null;

	/**
	 * @var PaginatorInterface
	 */
	protected PaginatorInterface $paginator;

	/**
	 * KnpResultSet constructor.
	 * @param PaginatorInterface $paginator
	 * @param Client $client
	 * @param Query $query
	 * @param string $term
	 */
	public function __construct(PaginatorInterface $paginator, Client $client, Query $query, string $term) {
		$this->query = $query;
		$this->term = $term;
		$this->client = $client;
		$this->paginator = $paginator;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\PaginatableResultSet::paginate()
	 */
	public function paginate(int $page = 1, int $limit = 10, array $options = array()): PaginationInterface {
		$this->lastResult = $this->paginator->paginate(array(
			$this->client,
			$this->query
		), $page, $limit, $options);

		$this->lastSolrResult = $this->lastResult->getCustomParameter('result');

		return $this->lastResult;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\ResultSetAdapter::getResults()
	 */
	public function getResults(int $offset = 0, ?int $limit = null): array {
		$oldStart = $this->query->getStart();
		$oldOffset = $this->query->getRows();

		$this->query->setStart($offset);
		if($limit) {
			$this->query->setRows($limit);
		}
		$this->lastSolrResult = $solrResult = $this->client->select($this->query);

		$this->query->setStart($oldStart);
		$this->query->setRows($oldOffset);

		$documents = array();
		foreach($solrResult->getDocuments() as $solrDocument) {
			$documents[] = \StingerSoft\SolrEntitySearchBundle\Model\Document::createFromSolariumResult($solrDocument);
		}

		return $documents;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\ResultSet::getExcerpt()
	 */
	public function getExcerpt(Document $document): ?string {
		/**
		 *
		 * @var Highlighting $highlighting
		 */
		$highlighting = $this->lastSolrResult->getHighlighting();
		$docHighlight = $highlighting->getResult($document->getFieldValue('id'));

		if(!$docHighlight) {
			return null;
		}
		$excerpts = $docHighlight->getField('content');

		return $excerpts ? \implode(' ', $excerpts) : null;
	}

	public function getCorrections(): array {
		$result = array();

		//Solr < 6.5
		// $spellcheckResult = $this->lastSolrResult->getSpellcheck();

		// Solr madness > 6.5
		$rawData = $this->lastSolrResult->getResponse()->getBody();

		$rawData = preg_replace_callback('/"collation":{"collationQuery"/i', function($match) {
			return '"collation_' . \uniqid('', true) . '":{"collationQuery"';
		}, $rawData);

		$data = \json_decode($rawData, true);

		if(!isset($data['spellcheck']) || !isset($data['spellcheck']['collations'])) {
			return $result;
		}
		foreach($data['spellcheck']['collations'] as $collation) {
			if(is_array($collation) && isset($collation['collationQuery']) && isset($collation['hits'])) {
				$item = new Correction();
				$item->setQuery($collation['collationQuery']);
				$item->setHits($collation['hits']);
				$result[] = $item;
			}
		}

		usort($result, static function(Correction $a, Correction $b) {
			return $b->getHits() - $a->getHits();
		});

		return $result;
	}
}
