<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area\SearchTerm;

use Magento\Framework\DataObject;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\Area\SearchTerm\ParserInterface;

/**
 * Extract city from Search Term.
 */
class CityParser implements ParserInterface
{
    private const CITY = 'city';
    private const POSTCODE = 'postcode';

    /**
     * @var DelimiterParser
     */
    private $parser;

    /**
     * @param DelimiterParser $parser
     */
    public function __construct(DelimiterParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $searchTerm, DataObject $dataObject): void
    {
        if (empty($dataObject->getData(self::POSTCODE))) {
            $dataObject->setData(self::CITY, $this->parser->getSearchQuery($searchTerm));
        } else {
            $dataObject->setData(self::CITY, '');
        }
    }
}
