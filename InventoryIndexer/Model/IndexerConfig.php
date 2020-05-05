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

    const XML_PATH_INDEXER_SOURCE_ITEM_STRATEGY = 'cataloginventory/indexer/source_items_strategy';
    const XML_PATH_INDEXER_SOURCE_STRATEGY = 'cataloginventory/indexer/source_strategy';

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
     * @return string
     */
    public function getActiveSourceItemIndexStrategy(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_INDEXER_SOURCE_ITEM_STRATEGY);
    }

    /**
     * @return string
     */
    public function getActiveSourceIndexStrategy(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_INDEXER_SOURCE_STRATEGY);
    }

}
