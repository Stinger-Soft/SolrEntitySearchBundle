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

use StingerSoft\EntitySearchBundle\Services\AbstractSearchService;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use StingerSoft\EntitySearchBundle\Model\Query;

class SearchService extends AbstractSearchService {
	
	use ContainerAwareTrait;

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::clearIndex()
	 */
	public function clearIndex() {
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::saveDocument()
	 */
	public function saveDocument(\StingerSoft\EntitySearchBundle\Model\Document $document) {
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::removeDocument()
	 */
	public function removeDocument(\StingerSoft\EntitySearchBundle\Model\Document $document) {
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::autocomplete()
	 */
	public function autocomplete($search, $maxResults = 10) {
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::search()
	 */
	public function search(Query $query) {
	}

	public function getIndexSize() {
	}
}