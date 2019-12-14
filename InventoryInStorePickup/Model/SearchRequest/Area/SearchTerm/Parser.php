<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area\SearchTerm;

/**
 * Parse Search Term.
 */
class Parser
{
    private const DELIMITER = ':';

    /**
     * Get country from Search Term.
     *
     * @param string $searchTerm
     *
     * @return string|null
     */
    public function getCountry(string $searchTerm): ?string
    {
        if (strpos($searchTerm, self::DELIMITER) === false) {
            return null;
        }
        $searchTerm = explode(self::DELIMITER, $searchTerm);

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
        if (strpos($searchTerm, self::DELIMITER) === false) {
            return $searchTerm;
        }
        $searchTerm = explode(self::DELIMITER, $searchTerm);

        return trim(current($searchTerm));
    }
}
