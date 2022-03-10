<?php
declare(strict_types=1);

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

use Solarium\Core\Query\DocumentInterface;
use StingerSoft\EntitySearchBundle\Model\DocumentAdapter;

class Document extends DocumentAdapter {

	public static function createFromSolariumResult(DocumentInterface $solrDocument): self {
		$document = new self();
		foreach($solrDocument->getFields() as $key => $value) {
			$document->addField($key, $value);
		}
		$document->setEntityType(self::getSingleValueFieldFromSolariumResult($document, 'entityType'));
		$document->setEntityClass(self::getSingleValueFieldFromSolariumResult($document, 'clazz'));
		$document->setEntityId(self::getSingleValueFieldFromSolariumResult($document, 'internalId'));

		// Map solr extractor properties to the document
		$contentType = $document->getFieldValue("attr_Content-Type");
		if($contentType !== null && $document->getFieldValue(\StingerSoft\EntitySearchBundle\Model\Document::FIELD_CONTENT_TYPE) === null) {
			$document->addField(\StingerSoft\EntitySearchBundle\Model\Document::FIELD_CONTENT_TYPE, $contentType);
		}
		return $document;
	}

	protected static function getSingleValueFieldFromSolariumResult(Document $document, string $fieldName) {
		$value = $document->getFieldValue($fieldName);
		if(\is_array($value)) {
			return current($value);
		}
		return $value;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::getFieldValue()
	 */
	public function getFieldValue(string $fieldName) {
		$fallBackFieldname = \strtolower($fieldName);
		if(!isset($this->fields[$fieldName]) && isset($this->fields[$fallBackFieldname])) {
			$fieldName = $fallBackFieldname;
		}
		$fallBackFieldname = 'attr_' . \strtolower($fieldName);
		if(!isset($this->fields[$fieldName]) && isset($this->fields[$fallBackFieldname])) {
			$fieldName = $fallBackFieldname;
		}
		return parent::getFieldValue($fieldName);
	}

}

