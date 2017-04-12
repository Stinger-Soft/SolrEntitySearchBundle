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

abstract class AbstractSchemaModification {

	protected $options = array();
	
	public function __construct(array $options = array()) {
		$this->options = $options;
	}

	public abstract function getCommandType();

	public function getRequiredFields() {
		return array(
			'name' 
		);
	}
	
	public function getOptions() {
		return $this->options;
	}

	public function setOption($key, $value) {
		$this->options[$key] = $value;
	}

	public function getOption($key) {
		isset($this->options[$key]) ? $this->options[$key] : null;
	}

	public function setName($name) {
		$this->setOption('name', $name);
	}

	public function getName() {
		return $this->getOption('name');
	}
}

