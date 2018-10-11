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

class AddField extends AbstractSchemaModification {

	public function getCommandType(): string {
		return Query::COMMAND_ADD_FIELD;
	}

	public function getRequiredFields(): array {
		return array(
			'name',
			'type'
		);
	}

	public function setType($type) {
		$this->setOption('type', $type);
	}

	public function getType() {
		return $this->getOption('type');
	}

	public function setIndexed($type) {
		$this->setOption('indexed', $type);
	}

	public function isIndexed() {
		return $this->getOption('indexed');
	}

	public function setStored($type) {
		$this->setOption('stored', $type);
	}

	public function isStored() {
		return $this->getOption('stored');
	}

	public function setMultiValued($type) {
		$this->setOption('stored', $type);
	}

	public function isMultiValued() {
		return $this->getOption('stored');
	}
}

