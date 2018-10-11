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

namespace StingerSoft\SolrEntitySearchBundle\Command;

use StingerSoft\SolrEntitySearchBundle\Services\SearchService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateSchemaCommand extends Command {

	protected static $defaultName = 'stinger:search-solr:init';

	protected $searchService;

	public function __construct(SearchService $searchService) {
		parent::__construct();
		$this->searchService = $searchService;

	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Console\Command\Command::configure()
	 */
	protected function configure() {
		$this->setDescription('Clears the configured search index');
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->searchService->initializeBackend();
	}
}

