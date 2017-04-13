<?php

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

use Doctrine\ORM\Query;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use StingerSoft\EntitySearchBundle\Model\Document;
use StingerSoft\EntitySearchBundle\Model\PaginatableResultSet;
use StingerSoft\EntitySearchBundle\Model\ResultSetAdapter;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use StingerSoft\EntitySearchBundle\Model\Result\Correction;

class KnpResultSet extends ResultSetAdapter implements PaginatableResultSet, ContainerAwareInterface {
	
	use ContainerAwareTrait;

	/**
	 *
	 * @var \Solarium\QueryType\Select\Query\Query
	 */
	protected $query = null;

	/**
	 *
	 * @var string
	 */
	protected $term = null;

	/**
	 *
	 * @var \Solarium\Client
	 */
	protected $client = null;

	/**
	 *
	 * @var SlidingPagination|Document[]
	 */
	protected $lastResult = null;

	/**
	 *
	 * @var \Solarium\QueryType\Select\Result\Result
	 */
	protected $lastSolrResult = null;

	/**
	 *
	 * @param \Solarium\Client $client        	
	 * @param \Solarium\QueryType\Select\Query\Query $query        	
	 * @param string $term        	
	 */
	public function __construct($client, $query, $term) {
		$this->query = $query;
		$this->term = $term;
		$this->client = $client;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\PaginatableResultSet::paginate()
	 */
	public function paginate($page = 1, $limit = 10, array $options = array()) {
		$paginator = $this->container->get('knp_paginator');
		$this->lastResult = $paginator->paginate(array(
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
	public function getResults($offset = 0, $limit = null) {
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
	public function getExcerpt(Document $document) {
		/**
		 *
		 * @var \Solarium\QueryType\Select\Result\Highlighting\Highlighting $highlighting
		 */
		$highlighting = $this->lastSolrResult->getHighlighting();
		$docHighlight = $highlighting->getResult($document->getFieldValue('id'));
		
		if(!$docHighlight)
			return null;
		
		return $docHighlight->getField('content');
	}

	public function getCorrections() {
		$result = array();
		
		//Solr < 6.5
		// $spellcheckResult = $this->lastSolrResult->getSpellcheck();
		
		// Solr madness > 6.5
		$rawData = $this->lastSolrResult->getResponse()->getBody();
		
		$rawData = preg_replace_callback('/"collation":{"collationQuery"/i', function ($match) {
			return '"collation_'.uniqid().'":{"collationQuery"';
		}, $rawData);
		
		$data = json_decode($rawData, true);
		
		if(!isset($data['spellcheck']) || !isset($data['spellcheck']['collations'])) {
			return null;
		}
		foreach($data['spellcheck']['collations'] as $collation) {
			$item = new Correction();
			$item->setQuery($collation['collationQuery']);
			$item->setHits($collation['hits']);
			$result[] = $item;
		}
		
		usort($result, function(Correction $a, Correction $b)
		{
			return $b->getHits() - $a->getHits();
		});
		
		return $result;
	}
}