<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area\SearchTerm;

use Magento\Directory\Helper\Data;
use Magento\Framework\DataObject;
use Magento\InventoryInStorePickupApi\Model\SearchResult\Area\SearchTerm\HandlerInterface;

/**
 * Provide Default country for area search.
 */
class CountryHandler implements HandlerInterface
{
    public const COUNTRY = 'country';

    /**
     * @var Data
     */
    private $data;
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param Data $data
     * @param Parser $parser
     */
    public function __construct(Data $data, Parser $parser)
    {
        $this->data = $data;
        $this->parser = $parser;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $searchTerm, DataObject $result): void
    {
        $result->setData(self::COUNTRY, $this->parser->getCountry($searchTerm) ?? $this->data->getDefaultCountry());
    }
}
