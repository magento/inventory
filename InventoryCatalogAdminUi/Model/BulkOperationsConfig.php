<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class BulkOperationsConfig
{
    public const XML_PATH_ASYNC_ENABLED = 'cataloginventory/bulk_operations/async';
    public const XML_PATH_BATCH_SIZE = 'cataloginventory/bulk_operations/batch_size';

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
     * Checks if asynchronous bulk operations are enabled in the configuration settings.
     *
     * @return bool
     */
    public function isAsyncEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_ASYNC_ENABLED);
    }

    /**
     * Gets the batch size for bulk operations from configuration, ensuring it is at least 1.
     *
     * @return int
     */
    public function getBatchSize(): int
    {
        return (int) max(1, (int) $this->scopeConfig->getValue(self::XML_PATH_BATCH_SIZE));
    }
}
