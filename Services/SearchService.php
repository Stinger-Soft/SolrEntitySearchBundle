<?php

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

use Solarium\Client;
use StingerSoft\EntitySearchBundle\Model\Document;
use StingerSoft\EntitySearchBundle\Model\Query;
use StingerSoft\EntitySearchBundle\Model\Result\FacetSetAdapter;
use StingerSoft\EntitySearchBundle\Services\AbstractSearchService;
use StingerSoft\SolrEntitySearchBundle\Model\KnpResultSet;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class SearchService extends AbstractSearchService implements ContainerAwareInterface {
	
	use ContainerAwareTrait;

	/**
	 *
	 * @var ClientConfiguration
	 */
	protected $configuration;

	public function __construct(ClientConfiguration $configuration) {
		$this->configuration = $configuration;
	}

	public function initializeBackend() {
		// Add common field
		// Add field textSuggest
		// Copyfield title -> textSuggest
	}

	/**
	 *
	 * @return \Solarium\Client
	 */
	protected function getClient() {
		$config = array(
			'endpoint' => array(
				'localhost' => array(
					'host' => $this->configuration->ipAddress,
					'port' => $this->configuration->port,
					'path' => $this->configuration->path 
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

	public function ping() {
		$client = $this->getClient();
		
		// create a ping query
		$ping = $client->createPing();
		try {
			$result = $client->ping($ping);
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
	public function saveDocument(\StingerSoft\EntitySearchBundle\Model\Document $document) {
		$client = $this->getClient();
		
		$update = $client->createUpdate();
		
		// create a new document for the data
		$doc = $update->createDocument();
		$doc->id = $this->createIdFromDocument($document);
		$doc->internalId = json_encode($document->getEntityId());
		$doc->clazz = $document->getEntityClass();
		
		foreach($document->getFields() as $key => $value) {
			$doc->setField($key, $value);
		}
		
		$update->addDocuments(array(
			$doc 
		));
		$update->addCommit();
		
		// this executes the query and returns the result
		$client->update($update);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::removeDocument()
	 */
	public function removeDocument(\StingerSoft\EntitySearchBundle\Model\Document $document) {
		$client = $this->getClient();
		
		// get an update query instance
		$update = $client->createUpdate();
		
		// add the delete query and a commit command to the update query
		$update->addDeleteQuery('id:' . $this->createIdFromDocument($document, true));
		$update->addCommit();
		
		// this executes the query and returns the result
		$client->update($update);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::autocomplete()
	 */
	public function autocomplete($search, $maxResults = 10) {
		$client = $this->getClient();
		
		// get a suggester query instance
		$query = $client->createSuggester();
		$query->setQuery($search); // multiple terms
		
		$query->addParam('suggest.dictionary', 'PecSuggester');
		$query->setDictionary('PecSuggester');
		
		$query->setHandler('suggest');
		$query->setOnlyMorePopular(true);
		
		$query->setCount($maxResults);
		$query->addParam('suggest.count', $maxResults);
		
		$query->setCollate(true);
		
		// this executes the query and returns the result
		$resultset = $client->suggester($query);
		
		$results = json_decode($resultset->getResponse()->getBody(), true);
		$results = $results['suggest']['PecSuggester'];
		
		$result = array();
		
		// display results for each term
		foreach($results as $term => $termResult) {
			foreach($termResult['suggestions'] as $term) {
				$result[] = $term['term'];
			}
		}
		return $result;
	}

	protected function getBasicQuery(Client $client, Query $query) {
		$client = $this->getClient();
		$solrQuery = $client->createSelect();
		$solrQuery->setQuery($query->getSearchTerm());
		
		$hl = $solrQuery->getHighlighting();
		$hl->setSnippets(3);
		$hl->setFields('content');
		$hl->setSimplePrefix('<em>');
		$hl->setSimplePostfix('</em>');
		
		return $solrQuery;
	}

	protected function getFacetedQuery(Client $client, Query $query) {
		$solrQuery = $this->getBasicQuery($client, $query);
		
		// get the facetset component
		$facetSet = $solrQuery->getFacetSet();
		if($query->getUsedFacets() != null) {
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
			$facetKey = $this->escapeFacetKey($facetKey);
			$solrQuery->createFilterQuery($facetKey)->setQuery($facetKey . ':(' . implode(' OR ', $values) . ')');
		}
		return $solrQuery;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::search()
	 */
	public function search(Query $query) {
		$client = $this->getClient();
		
		$filteredQuery = $this->getFilteredFacetedQuery($client, $query);
		$facetQuery = $this->getFacetedQuery($client, $query);
		$facetresultset = $client->select($facetQuery);
		
		$result = new KnpResultSet($client, $filteredQuery, $query->getSearchTerm());
		$result->setContainer($this->container);
		
		$facetSet = new FacetSetAdapter();
		foreach($facetresultset->getFacetSet() as $facetKey => $facetValues) {
			$facetKey = $this->unescapeFacetKey($facetKey);
			foreach($facetValues as $facetValue => $count) {
				$facetSet->addFacetValue($facetKey, $facetValue, $count);
			}
		}
		$result->setFacets($facetSet);
		
		return $result;
	}

	public function getIndexSize() {
		$client = $this->getClient();
		$query = $client->createSelect();
		$query->setRows(0);
		$resultset = $client->execute($query);
		return $resultset->getNumFound();
	}

	protected function escapeFacetKey($facetKey) {
		return $facetKey == Document::FIELD_TYPE ? 'clazz' : $facetKey;
	}

	protected function unescapeFacetKey($facetKey) {
		return $facetKey == 'clazz' ? Document::FIELD_TYPE : $facetKey;
	}
}