<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area\SearchTerm;

use Magento\Directory\Helper\Data;
use Magento\Framework\DataObject;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\Area\SearchTerm\DelimiterConfig;
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
     * @var DelimiterConfig
     */
    private $delimiterConfig;

    /**
     * @param Data $data
     * @param DelimiterConfig $delimiterConfig
     */
    public function __construct(Data $data, DelimiterConfig $delimiterConfig)
    {
        $this->data = $data;
        $this->delimiterConfig = $delimiterConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $searchTerm, DataObject $result): void
    {
        $result->setData(self::COUNTRY, $this->getCountry($searchTerm) ?? $this->data->getDefaultCountry());
    }

    /**
     * Get country from Search Term.
     *
     * @param string $searchTerm
     *
     * @return string|null
     */
    private function getCountry(string $searchTerm): ?string
    {
        if (strpos($searchTerm, $this->delimiterConfig->getDelimiter()) === false) {
            return null;
        }
        $searchTerm = explode($this->delimiterConfig->getDelimiter(), $searchTerm);

        return trim(end($searchTerm) ?? '');
    }
}
