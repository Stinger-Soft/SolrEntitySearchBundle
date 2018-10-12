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

namespace StingerSoft\SolrEntitySearchBundle\QueryType\Schema;

use Solarium\Core\Client\Request;
use Solarium\Core\Query\AbstractRequestBuilder as BaseRequestBuilder;
use Solarium\Core\Query\QueryInterface;
use StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Query;

class RequestBuilder extends BaseRequestBuilder {

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Solarium\Core\Query\AbstractRequestBuilder::build()
	 */
	public function build(QueryInterface $query): Request {
		$request = parent::build($query);
		$request->setMethod(Request::METHOD_POST);
		$request->setRawData($this->getRawData($query));
		return $request;
	}

	/**
	 * Generates raw POST data.
	 *
	 * Each commandtype is delegated to a separate builder method.
	 *
	 * @param Query $query
	 *
	 * @throws \RuntimeException
	 *
	 * @return string
	 */
	public function getRawData(QueryInterface $query): string {
		$data = array();
		foreach($query->getCommands() as $command) {
			$data[$command->getCommandType()] = $command->getOptions();
		}
		return json_encode($data);
	}
}

