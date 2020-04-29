<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class IndexerConfig
{
    const XML_PATH_INDEXER_ASYNC_ENABLED = 'cataloginventory/indexer/async';

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
     * @return bool
     */
    public function isAsyncIndexerEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_INDEXER_ASYNC_ENABLED);
    }
}
