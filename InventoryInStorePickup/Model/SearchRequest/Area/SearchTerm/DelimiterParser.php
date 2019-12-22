<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area\SearchTerm;

use Magento\InventoryInStorePickupApi\Model\SearchRequest\Area\SearchTerm\DelimiterConfig;

/**
 * Parse Search Term.
 */
class DelimiterParser
{
    /**
     * @var DelimiterConfig
     */
    private $delimiterConfig;

    /**
     * @param DelimiterConfig $delimiterConfig
     */
    public function __construct(DelimiterConfig $delimiterConfig)
    {
        $this->delimiterConfig = $delimiterConfig;
    }

    /**
     * Get country from Search Term.
     *
     * @param string $searchTerm
     *
     * @return string|null
     */
    public function getCountry(string $searchTerm): ?string
    {
        if (strpos($searchTerm, $this->delimiterConfig->getDelimiter()) === false) {
            return null;
        }
        $searchTerm = explode($this->delimiterConfig->getDelimiter(), $searchTerm);

        return trim(end($searchTerm));
    }

    /**
     * Parse and return search query from search term.
     *
     * @param string $searchTerm
     *
     * @return string
     */
    public function getSearchQuery(string $searchTerm): string
    {
        if (strpos($searchTerm, $this->delimiterConfig->getDelimiter()) === false) {
            return $searchTerm;
        }
        $searchTerm = explode($this->delimiterConfig->getDelimiter(), $searchTerm);

        return trim(current($searchTerm));
    }
}
