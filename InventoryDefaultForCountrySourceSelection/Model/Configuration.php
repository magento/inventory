<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDefaultForCountrySourceSelection\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * Algorithm configuration
 */
class Configuration
{
    private const XML_PATH_ADDITIONAL_ALGORITHM =
        'cataloginventory/source_selection_default_for_country/additional_algorithm';
    private const XML_PATH_EXCLUDE_UNMATCHED =
        'cataloginventory/source_selection_default_for_country/exclude_unmatched';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Configuration constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get additional algorithm code
     *
     * @param null|string|bool|int|Store $store
     * @return string|null
     */
    public function getAdditionalAlgorithmCode($store = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ADDITIONAL_ALGORITHM,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get exclude_unmatched config flag
     *
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function isExcludeUnmatchedEnabled($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_EXCLUDE_UNMATCHED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
