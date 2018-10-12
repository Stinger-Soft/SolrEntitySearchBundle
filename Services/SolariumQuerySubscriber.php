<?php
declare(strict_types=1);

namespace StingerSoft\SolrEntitySearchBundle\Services;

use Knp\Component\Pager\Event\ItemsEvent;
use StingerSoft\SolrEntitySearchBundle\Model\Document;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Solarium query pagination.
 *
 * based on the work of
 *
 * @author Paweł Jędrzejewski <pjedrzejewski@diweb.pl>
 */
class SolariumQuerySubscriber implements EventSubscriberInterface {

	public static function getSubscribedEvents() {
		return array(
			'knp_pager.items' => array(
				'items',
				1
			)  /* triggers before a standard array subscriber */
		);
	}

	public function items(ItemsEvent $event): void {
		if(\is_array($event->target) && 2 === count($event->target)) {
			[$client, $query] = array_values($event->target);

			if($client instanceof \Solarium\Client && $query instanceof \Solarium\QueryType\Select\Query\Query) {
				$query->setStart($event->getOffset())->setRows($event->getLimit());
				$solrResult = $client->select($query);

				$event->items = array();
				foreach($solrResult->getDocuments() as $solrDocument) {
					$event->items[] = Document::createFromSolariumResult($solrDocument);
				}
				$event->count = $solrResult->getNumFound();
				$event->setCustomPaginationParameter('result', $solrResult);
				$event->stopPropagation();
			}
		}
	}
}
