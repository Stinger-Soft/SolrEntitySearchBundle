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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * A Keen Metronic Admin Theme (http://www.keenthemes.com/) Bundle for Symfony2
 * with some additional libraries and PecPlatform specific customization.
 */
class StingerSoftSolrEntitySearchExtension extends Extension {

	/**
	 *
	 * {@inheritDoc}
	 *
	 */
	public function load(array $configs, ContainerBuilder $container) {
		$configuration = new Configuration();
		$config = $this->processConfiguration($configuration, $configs);

		$loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
		$loader->load('services.yml');

		$searchService = $container->getDefinition('stinger_soft.solr_entity_search.search_service');
		$searchService->setArgument('$config', $config);
//		$searchService->addArgument($container->get('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE));
	}
}