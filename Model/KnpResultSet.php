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
use Doctrine\ORM\QueryBuilder;
use StingerSoft\EntitySearchBundle\Model\PaginatableResultSet;
use StingerSoft\EntitySearchBundle\Model\ResultSetAdapter;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class KnpResultSet extends ResultSetAdapter implements PaginatableResultSet, ContainerAwareInterface {
	
	use ContainerAwareTrait;

	protected $query = null;

	protected $term = null;
	
	protected $client= null;

	/**
	 *
	 * @param Query|QueryBuilder $items        	
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
		return $paginator->paginate(array($this->client, $this->query), $page, $limit, $options);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\ResultSetAdapter::getResults()
	 */
	public function getResults($offset = 0, $limit = null) {
		return $this->paginate(1);
	}
}