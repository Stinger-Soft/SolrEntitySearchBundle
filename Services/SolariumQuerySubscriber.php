<?php

namespace StingerSoft\SolrEntitySearchBundle\Services;

use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use StingerSoft\EntitySearchBundle\Model\DocumentAdapter;
use StingerSoft\SolrEntitySearchBundle\Model\Document;

/**
 * Solarium query pagination.
 *
 * based on the work of
 * 
 * @author Paweł Jędrzejewski <pjedrzejewski@diweb.pl>
 */
class SolariumQuerySubscriber implements EventSubscriberInterface {

	public function items(ItemsEvent $event) {
		if(is_array($event->target) && 2 == count($event->target)) {
			$values = array_values($event->target);
			list($client, $query) = $values;
			
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

	public static function getSubscribedEvents() {
		return array(
			'knp_pager.items' => array(
				'items',
				1 
			)  /* triggers before a standard array subscriber */
		);
	}
}
