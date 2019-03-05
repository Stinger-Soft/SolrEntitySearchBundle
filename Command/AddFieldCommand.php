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

namespace StingerSoft\SolrEntitySearchBundle\Command;

use StingerSoft\SolrEntitySearchBundle\Services\SearchService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddFieldCommand extends Command {

	protected static $defaultName = 'stinger:search-solr:add-field';

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
		$this->setDescription('Adds a single field to the search index');
		$this->addArgument('name', InputArgument::REQUIRED);
		$this->addArgument('type', InputArgument::REQUIRED);
		$this->addOption('multivalued', 'm', InputOption::VALUE_NONE);
		$this->addOption('stored', 's', InputOption::VALUE_NONE);
		$this->addOption('indexed', 'i', InputOption::VALUE_NONE);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$name = $input->getArgument('name');
		$type = $input->getArgument('type');
		$multivalued = $input->getOption('multivalued');
		$stored = $input->getOption('stored');
		$indexed = $input->getOption('indexed');
		$this->searchService->addField($name, $type, $multivalued, $stored, $indexed);
	}
}

