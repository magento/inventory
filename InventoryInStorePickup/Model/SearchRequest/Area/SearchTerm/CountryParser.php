<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area\SearchTerm;

use Magento\Directory\Helper\Data;
use Magento\Framework\DataObject;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\Area\SearchTerm\ParserInterface;

/**
 * Extract country from Search Term or provide default country for area search.
 */
class CountryParser implements ParserInterface
{
    private const COUNTRY = 'country';

    /**
     * @var Data
     */
    private $data;
    /**
     * @var DelimiterParser
     */
    private $parser;

    /**
     * @param Data $data
     * @param DelimiterParser $parser
     */
    public function __construct(Data $data, DelimiterParser $parser)
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
