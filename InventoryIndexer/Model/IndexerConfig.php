<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Reindex strategy config provider.
 * @api
 */
class IndexerConfig
{
    /**
     * Reindex strategy config path.
     */
    private const XML_PATH_INDEXER_STRATEGY = 'cataloginventory/indexer/strategy';

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
     * Return active strategy for reindex process.
     *
     * @return string
     */
    public function getActiveIndexStrategy(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_INDEXER_STRATEGY);
    }
}
