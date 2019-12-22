<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model\SearchRequest\Area\SearchTerm;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Search Term Delimiter Configuration.
 * @api
 */
class DelimiterConfig
{
    private const XML_PATH_SEARCH_TERM_DELIMITER = 'advanced/in_store_pickup_api/search_term_delimiter';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get Delimiter from configurations.
     *
     * @return string
     */
    public function getDelimiter(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_SEARCH_TERM_DELIMITER);
    }
}
