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

namespace StingerSoft\SolrEntitySearchBundle;

use StingerSoft\EntitySearchBundle\StingerSoftEntitySearchBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 */
class StingerSoftSolrEntitySearchBundle extends Bundle {

	public static function getRequiredBundles($env) {
		$bundles = array();
		$bundles['StingerSoftSolrEntitySearchBundle'] = '\StingerSoft\SolrEntitySearchBundle\StingerSoftSolrEntitySearchBundle';
		$bundles = array_merge($bundles, StingerSoftEntitySearchBundle::getRequiredBundles($env));
		return $bundles;
	}
}