<?php

/*
 * This file is part of the Stinger Entity Search package.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace StingerSoft\SolrEntitySearchBundle\Model;

use StingerSoft\EntitySearchBundle\Model\DocumentAdapter;
use Solarium\QueryType\Select\Result\DocumentInterface;

class Document extends DocumentAdapter {

	public static function createFromSolariumResult(DocumentInterface $solrDocument) {
		$document = new Document();
		foreach($solrDocument->getFields() as $key => $value) {
			$document->addField($key, $value);
		}
		$document->setEntityType($document->getFieldValue('entityType'));
		$document->setEntityClass($document->getFieldValue('clazz'));
		$document->setEntityId($document->getFieldValue('internalId'));

		// Map solr extractor properties to the document
		$contentType = $document->getFieldValue("attr_Content-Type");
		if($contentType !== null && $document->getFieldValue(DocumentAdapter::FIELD_CONTENT_TYPE) === null) {
			$document->addField(DocumentAdapter::FIELD_CONTENT_TYPE, $contentType);
		}
		return $document;
	}
	
}

