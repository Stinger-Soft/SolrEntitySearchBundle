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

namespace StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query;

use Solarium\Core\Query\AbstractQuery as BaseQuery;
use Solarium\Core\Query\ResponseParserInterface;
use Solarium\Exception\InvalidArgumentException;
use StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Command\AbstractSchemaModification;
use StingerSoft\SolrEntitySearchBundle\QueryType\Schema\RequestBuilder;

class Query extends BaseQuery {

	/**
	 * Update command add field.
	 */
	const COMMAND_ADD_FIELD = 'add-field';

	/**
	 * Update command add copy field.
	 */
	const COMMAND_ADD_COPY_FIELD = 'add-copy-field';

	/**
	 * TODO add resultclass and document class
	 * @var array
	 */
	protected $options = array(
		'handler'       => 'schema',
		'resultclass'   => 'Solarium\QueryType\Update\Result',
		'documentclass' => 'Solarium\QueryType\Update\Query\Document\Document',
		'omitheader'    => false
	);

	protected $commandTypes = array(
		self::COMMAND_ADD_FIELD      => 'StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Command\AddField',
		self::COMMAND_ADD_COPY_FIELD => 'StingerSoft\SolrEntitySearchBundle\QueryType\Schema\Query\Command\AddCopyField',
	);

	protected $commands = array();

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Solarium\Core\Query\QueryInterface::getType()
	 */
	public function getType(): string {
		return 'schema';
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Solarium\Core\Query\QueryInterface::getRequestBuilder()
	 */
	public function getRequestBuilder(): RequestBuilder {
		return new RequestBuilder();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Solarium\Core\Query\QueryInterface::getResponseParser()
	 */
	public function getResponseParser(): ?ResponseParserInterface {
		return null;
	}

	/**
	 * Get all commands for this update query.
	 *
	 * @return AbstractSchemaModification[]
	 */
	public function getCommands(): array {
		return $this->commands;
	}

	/**
	 * Create a command instance.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param string $type
	 * @param mixed $options
	 *
	 * @return AbstractSchemaModification
	 */
	public function createCommand($type, $options = null): AbstractSchemaModification {
		$type = strtolower($type);

		if(!isset($this->commandTypes[$type])) {
			throw new InvalidArgumentException("Update commandtype unknown: " . $type);
		}

		$class = $this->commandTypes[$type];

		return new $class($options);
	}

	/**
	 * Add a command to this update query.
	 *
	 * The command must be an instance of one of the Solarium\QueryType\Update_*
	 * classes.
	 *
	 * @param string $key
	 * @param object $command
	 *
	 * @return self Provides fluent interface
	 */
	public function add(string $key, $command): self {
		if('' !== $key) {
			$this->commands[$key] = $command;
		} else {
			$this->commands[] = $command;
		}

		return $this;
	}

	/**
	 * Remove a command.
	 *
	 * You can remove a command by passing its key or by passing the command instance.
	 *
	 * @param string|\Solarium\QueryType\Update\Query\Command\AbstractCommand $command
	 *
	 * @return self Provides fluent interface
	 */
	public function remove($command): self {
		if(is_object($command)) {
			foreach($this->commands as $key => $instance) {
				if($instance === $command) {
					unset($this->commands[$key]);
					break;
				}
			}
		} else {
			if(isset($this->commands[$command])) {
				unset($this->commands[$command]);
			}
		}

		return $this;
	}
}

