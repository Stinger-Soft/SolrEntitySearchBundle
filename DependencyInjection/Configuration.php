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

namespace StingerSoft\SolrEntitySearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface {

	/**
	 *
	 * {@inheritDoc}
	 *
	 */
	public function getConfigTreeBuilder() {
		$treeBuilder = new TreeBuilder('stinger_soft_solr_entity_search');

		// @formatter:off
		$treeBuilder->getRootNode()->children()
			->scalarNode('ipaddress')->defaultValue('127.0.0.1')->end()
			->scalarNode('port')->defaultValue(8983)->end()
			->scalarNode('path')->defaultValue('/solr/platform/')->end()
		->end();
		// @formatter:on

		return $treeBuilder;
	}
}
