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
namespace StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Command;

use StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Query;

class AddCopyField extends AbstractSchemaModification {
	
	public function getCommandType() {
		return Query::COMMAND_ADD_COPY_FIELD;
	}

	public function getRequiredFields() {
		return array(
			'source',
			'dest'
		);
	}

	public function setSource($value) {
		$this->setOption('source', $value);
	}

	public function getSource() {
		return $this->getOption('source');
	}
	
	public function setDestination($value) {
		$this->setOption('dest', $value);
	}
	
	public function getDestination() {
		return $this->getOption('dest');
	}
	
}

