<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Observer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;

/**
 * Create source items during product import process for single source mode.
 */
class CreateSourceItemsObserver implements ObserverInterface
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemAllowedForProductType;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemAllowedForProductType
     * @param ResourceConnection $resource
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        DefaultSourceProviderInterface $defaultSourceProvider,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemAllowedForProductType,
        ResourceConnection $resource
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->isSourceItemAllowedForProductType = $isSourceItemAllowedForProductType;
        $this->resource = $resource;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $adapter = $observer->getAdapter();
        if (!$this->isSingleSourceMode->execute() || $adapter->getBehavior() === 'delete') {
            return;
        }

        $data = [];
        $products = $observer->getBunch();
        $sourceCode = $this->defaultSourceProvider->getCode();
        foreach ($products as $product) {
            if ($this->isSourceItemAllowedForProductType->execute($product['product_type'])) {
                $data[] = [
                    SourceItemInterface::SOURCE_CODE => $sourceCode,
                    SourceItemInterface::SKU => $product['sku'],
                    SourceItemInterface::QUANTITY => $product['qty'] ?? 0,
                    SourceItemInterface::STATUS => $product['is_in_stock'] ?? 0,
                ];
            }
        }
        if ($data) {
            $this->resource->getConnection()->insertOnDuplicate(
                $this->resource->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM),
                $data
            );
        }
    }
}
