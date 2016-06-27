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
namespace StingerSoft\SolrEntitySearchBundle\Services;

use StingerSoft\EntitySearchBundle\Services\AbstractSearchService;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class SearchService extends AbstractSearchService {

	use ContainerAwareTrait;
}