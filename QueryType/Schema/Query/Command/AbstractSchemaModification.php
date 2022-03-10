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

namespace StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Command;

abstract class AbstractSchemaModification {

	protected array $options;

	public function __construct(array $options = array()) {
		$this->options = $options;
	}

	abstract public function getCommandType(): string;

	public function getRequiredFields(): array {
		return array(
			'name'
		);
	}

	public function getOptions(): array {
		return $this->options;
	}

	public function setOption(string $key, $value): void {
		$this->options[$key] = $value;
	}

	public function getOption(string $key) {
		return $this->options[$key] ?? null;
	}

	public function setName(string $name): void {
		$this->setOption('name', $name);
	}

	public function getName(): string {
		return $this->getOption('name');
	}
}

