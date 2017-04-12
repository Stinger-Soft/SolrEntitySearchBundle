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
use Solarium\Core\Query\QueryInterface;
use StingerSoft\EntitySearchBundle\Model\PaginatableResultSet;
use StingerSoft\EntitySearchBundle\Model\ResultSetAdapter;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use StingerSoft\EntitySearchBundle\Model\Document;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;

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
	 * @var SlidingPagination|Document[]
	 */
	protected $lastResult = null;

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
		$solrResult = $this->client->select($this->query);
		
		$this->query->setStart($oldStart);
		$this->query->setRows($oldOffset);
		
		$documents = array();
		foreach($solrResult->getDocuments() as $solrDocument) {
			$documents[] = Document::createFromSolariumResult($solrDocument);
		}
		
		return $documents;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\ResultSet::getExcerpt()
	 */
	public function getExcerpt(Document $document) {
		
		/**
		 * @var \Solarium\QueryType\Select\Result\Result $solrResult
		 */
		$solrResult = $this->lastResult->getCustomParameter('result');
		
		/**
		 * @var \Solarium\QueryType\Select\Result\Highlighting\Highlighting $highlighting
		 */
		$highlighting = $solrResult->getHighlighting();
		$docHighlight =  $highlighting->getResult($document->getFieldValue('id'));

		if(!$docHighlight) return null;
		
		return $docHighlight->getField('content');
	}
}