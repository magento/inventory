<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area\SearchTerm;

use Magento\Framework\DataObject;
use Magento\InventoryInStorePickupApi\Model\SearchResult\Area\SearchTerm\HandlerInterface;

/**
 * Extract city from Search Term.
 */
class CityHandler implements HandlerInterface
{
    public const CITY = 'city';
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param Parser $parser
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $searchTerm, DataObject $dataObject): void
    {
        if (empty($dataObject->getData(PostCodeHandler::POSTCODE))) {
            $dataObject->setData(self::CITY, $this->parser->getSearchQuery($searchTerm));
        } else {
            $dataObject->setData(self::CITY, '');
        }
    }
}
