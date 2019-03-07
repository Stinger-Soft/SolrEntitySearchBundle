<?php
declare(strict_types=1);

/*
 * This file is part of the Stinger Solr Entity Search package.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StingerSoft\SolrEntitySearchBundle\Services;

use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Solarium\Client;
use Solarium\Exception\HttpException;
use StingerSoft\EntitySearchBundle\Model\Document;
use StingerSoft\EntitySearchBundle\Model\Query;
use StingerSoft\EntitySearchBundle\Model\Result\FacetSetAdapter;
use StingerSoft\EntitySearchBundle\Model\ResultSet;
use StingerSoft\EntitySearchBundle\Services\AbstractSearchService;
use StingerSoft\PhpCommons\String\Utils;
use StingerSoft\SolrEntitySearchBundle\Model\KnpResultSet;

class SearchService extends AbstractSearchService {

	/**
	 *
	 * @var ClientConfiguration
	 */
	protected $configuration;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @var PaginatorInterface
	 */
	protected $paginator;

	public function __construct(PaginatorInterface $paginator, array $config, LoggerInterface $logger = null) {
		$this->configuration = new ClientConfiguration($config);
		$this->logger = $logger;
		$this->paginator = $paginator;
	}

	public function initializeBackend() {
		$this->addField('title', 'strings');
		$this->addField('author', 'string', false);
		$this->addField('clazz', 'string', false);
		$this->addField('entityType', 'string', false);
		$this->addField('internalId', 'string', false);
		$this->addField('content', 'text_general');
		$this->addField('editors', 'strings');
		$this->addField('textSuggest', 'strings');
		$this->addCopyField('*', '_text_');
		$this->addCopyField('title', 'textSuggest');
	}

	public function addField($name, $type, $multivalued = true, $stored = true, $indexed = true) {
		$client = $this->getClient();
		$query = new \StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Query();
		/**
		 *
		 * @var \StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Command\AddField $command
		 */
		$command = $query->createCommand(\StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Query::COMMAND_ADD_FIELD, array(
			'name' => $name,
			'type' => $type
		));
		$command->setMultiValued($multivalued);
		$command->setStored($stored);
		$command->setIndexed($indexed);
		$query->add('add_' . $name, $command);
		try {
			$client->execute($query);
		}catch(HttpException $ex) {
			// field exists, replacing it..
			if(strstr($ex->getBody(), 'already') === false) {
				$this->replaceField($name, $type, $multivalued, $stored, $indexed);
			}
		}
	}

	public function replaceField($name, $type, $multivalued = true, $stored = true, $indexed = true) {
		$client = $this->getClient();
		$query = new \StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Query();
		/**
		 *
		 * @var \StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Command\AddField $command
		 */
		$command = $query->createCommand(\StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Query::COMMAND_REPLACE_FIELD, array(
			'name' => $name,
			'type' => $type
		));
		$command->setMultiValued($multivalued);
		$command->setStored($stored);
		$command->setIndexed($indexed);
		$query->add('add_' . $name, $command);
		$client->execute($query);
	}

	public function addCopyField($source, $destination) {
		$client = $this->getClient();
		$query = new \StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Query();
		/**
		 *
		 * @var \StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Command\AddCopyField $command
		 */
		$command = $query->createCommand(\StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Query::COMMAND_ADD_COPY_FIELD, array());
		$command->setSource($source);
		$command->setDestination($destination);
		$query->add('copy_' . $source . '_' . $destination, $command);
		$client->execute($query);
	}

	public function ping() {
		$client = $this->getClient();

		// create a ping query
		$ping = $client->createPing();
		try {
			$client->ping($ping);
			return true;
		} catch(\Exception $e) {
			return false;
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::clearIndex()
	 */
	public function clearIndex() {
		$client = $this->getClient();
		$delete = $client->createUpdate();
		$delete->addDeleteQuery('*:*');
		$delete->addCommit();
		$client->update($delete);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::saveDocument()
	 */
	public function saveDocument(\StingerSoft\EntitySearchBundle\Model\Document $document): void {
		$client = $this->getClient();

		$query = $client->createUpdate();

		// create a new document for the data
		$doc = $query->createDocument();

		if($document->getFile()) {
			$filename = $document->getFile();
			if(!file_exists($filename)) {
				if($this->logger) {
					$this->logger->error('Can\'t find file ' . $filename);
				}
				return;
			}
			$query = $client->createExtract();
			$doc = $query->createDocument();

			$query->setUprefix('attr_');
			$query->setFile($filename);
			$query->setCommit(true);
			$query->setOmitHeader(true);
		}

		$doc->id = $this->createIdFromDocument($document);
		$doc->internalId = json_encode($document->getEntityId());
		$doc->clazz = $document->getEntityClass();
		$doc->entityType = $document->getEntityType();

		foreach($document->getFields() as $key => $value) {
			$doc->setField($key, $value);
		}

		try {
			if($document->getFile()) {
				$query->setDocument($doc);
				$query->setCommit(true);
				$client->extract($query);
			} else {
				$query->addDocuments(array(
					$doc
				));
				$query->addCommit(true);
				// this executes the query and returns the result
				$client->update($query);
			}
		} catch(\Exception $exception) {
			if($this->logger) {
				$this->logger->critical('Failed to save Document!!', ['exception' => $exception]);
			}
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::removeDocument()
	 */
	public function removeDocument(\StingerSoft\EntitySearchBundle\Model\Document $document): void {
		$client = $this->getClient();

		// get an update query instance
		$update = $client->createUpdate();

		// add the delete query and a commit command to the update query
		$update->addDeleteQuery('id:' . $this->createIdFromDocument($document, true));
		$update->addCommit();

		try {
			// this executes the query and returns the result
			$client->update($update);
		} catch(\Exception $exception) {
			if($this->logger) {
				$this->logger->critical('Failed to remove Document!!', $exception);
			}
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::autocomplete()
	 */
	public function autocomplete(string $search, int $maxResults = 10): array {
		$client = $this->getClient();

		// get a suggester query instance
		$query = $client->createSuggester();
		$query->setQuery($search); // multiple terms

		$query->addParam('suggest.dictionary', 'PecSuggester');
		$query->setDictionary('PecSuggester');

		$query->setHandler('suggest');

		$query->setCount($maxResults);
		$query->addParam('suggest.count', $maxResults);

		$query->addParam('suggest.build', 'true');

		// this executes the query and returns the result
		$resultset = $client->suggester($query);

		$results = json_decode($resultset->getResponse()->getBody(), true);
		$results = $results['suggest']['PecSuggester'];

		$result = array();

		// display results for each term
		foreach($results as $termResult) {
			foreach($termResult['suggestions'] as $term) {
				$result[] = $term['term'];
			}
		}
		return $result;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::search()
	 */
	public function search(Query $query): ResultSet {
		$client = $this->getClient();

		$filteredQuery = $this->getFilteredFacetedQuery($client, $query);
		$facetQuery = $this->getFacetedQuery($client, $query);

		$facetresultset = $client->select($facetQuery);

		$result = new KnpResultSet($this->paginator, $client, $filteredQuery, $query->getSearchTerm());

		$facetSet = new FacetSetAdapter();
		foreach($facetresultset->getFacetSet() as $facetKey => $facetValues) {
			$facetKey = $this->unescapeFacetKey($facetKey);
			foreach($facetValues as $facetValue => $count) {
				$facetSet->addFacetValue($facetKey, (string)$facetValue, $facetValue, (int)$count);
			}
		}
		$result->setFacets($facetSet);

		return $result;
	}

	public function getIndexSize(): int {
		$client = $this->getClient();
		$query = $client->createSelect();
		$query->setRows(0);
		$resultset = $client->execute($query);
		return $resultset->getNumFound();
	}

	/**
	 *
	 * @return \Solarium\Client
	 */
	protected function getClient(): Client {
		$config = array(
			'endpoint' => array(
				'localhost' => array(
					'host'    => $this->configuration->ipAddress,
					'port'    => $this->configuration->port,
					'path'    => $this->configuration->path,
					'timeout' => 10000
				)
			)
		);
		$client = new \Solarium\Client($config);
		return $client;
	}

	protected function createIdFromDocument(\StingerSoft\EntitySearchBundle\Model\Document $document, $forSearch = false) {
		$id = $document->getEntityClass() . '#' . json_encode($document->getEntityId());
		$id = $forSearch ? str_replace('\\', '\\\\', $id) : $id;
		return $id;
	}

	protected function getBasicQuery(Client $client, Query $query) {
		$solrQuery = $client->createSelect();
		$solrQuery->setQueryDefaultField('_text_');
		$solrQuery->setQuery($query->getSearchTerm());

		$hl = $solrQuery->getHighlighting();
		$hl->setSnippets(3);
		$hl->setFields('content');
		$hl->setSimplePrefix('<em>');
		$hl->setSimplePostfix('</em>');

		$solrQuery->addParam('spellcheck', 'on');
		$solrQuery->addParam('spellcheck.build', 'on');

		return $solrQuery;
	}

	protected function getFacetedQuery(Client $client, Query $query) {
		$solrQuery = $this->getBasicQuery($client, $query);

		// get the facetset component
		$facetSet = $solrQuery->getFacetSet();
		if($query->getUsedFacets() !== null) {
			foreach($query->getUsedFacets() as $facetKey) {
				$facetKey = $this->escapeFacetKey($facetKey);
				$facetField = $facetSet->createFacetField($facetKey);
				$facetField->setField($facetKey);
			}
		}

		return $solrQuery;
	}

	protected function getFilteredFacetedQuery(Client $client, Query $query) {
		$solrQuery = $this->getFacetedQuery($client, $query);
		foreach($query->getFacets() as $facetKey => $values) {
			if(count($values) <= 0)
				continue;
			$escapedValues = $this->escapeFacetValues($facetKey, $values);
			$facetKey = $this->escapeFacetKey($facetKey);
			$solrQuery->createFilterQuery($facetKey)->setQuery($facetKey . ':(' . implode(' OR ', $escapedValues) . ')');
		}
		return $solrQuery;
	}

	protected function escapeFacetValues($facetKey, array $facetValues) {
		if($facetKey === Document::FIELD_TYPE) {
			return array_map(function($value) {
				return str_replace('\\', '\\\\', $value);
			}, $facetValues);
		}
		return $facetValues;
	}

	protected function escapeFacetKey($facetKey) {
		$facetKey = $facetKey === Document::FIELD_TYPE ? 'entityType' : $facetKey;
//		$facetKey = $facetKey === Document::FIELD_CONTENT_TYPE ? 'attr_Content-Type' : $facetKey;
		return $facetKey;
	}

	protected function unescapeFacetKey($facetKey) {
		$facetKey = $facetKey === 'entityType' ? Document::FIELD_TYPE : $facetKey;
//		$facetKey = $facetKey == 'attr_Content-Type' ? Document::FIELD_CONTENT_TYPE : $facetKey;
		return $facetKey;
	}
}