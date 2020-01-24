<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area\SearchTerm;

use Magento\Framework\DataObject;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\Area\SearchTerm\DelimiterConfig;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\Area\SearchTerm\ParserInterface;

/**
 * Extract city from Search Term.
 */
class CityParser implements ParserInterface
{
    private const CITY = 'city';
    private const POSTCODE = 'postcode';

    /**
     * @var DelimiterConfig
     */
    private $delimiterConfig;

    /**
     * @param DelimiterConfig $delimiterConfig
     */
    public function __construct(
        DelimiterConfig $delimiterConfig
    ) {
        $this->delimiterConfig = $delimiterConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $searchTerm, DataObject $dataObject): void
    {
        if (empty($dataObject->getData(self::POSTCODE))) {
            $dataObject->setData(self::CITY, $this->getSearchQuery($searchTerm));
        } else {
            $dataObject->setData(self::CITY, '');
        }
    }

    /**
     * Parse and return search query from search term.
     *
     * @param string $searchTerm
     *
     * @return string
     */
    private function getSearchQuery(string $searchTerm): string
    {
        if (strpos($searchTerm, $this->delimiterConfig->getDelimiter()) === false) {
            return $searchTerm;
        }
        $searchTerm = explode($this->delimiterConfig->getDelimiter(), $searchTerm);

        return trim(current($searchTerm));
    }
}
