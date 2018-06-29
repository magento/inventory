<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Observer;

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
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemAllowedForProductType
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        DefaultSourceProviderInterface $defaultSourceProvider,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemAllowedForProductType
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->isSourceItemAllowedForProductType = $isSourceItemAllowedForProductType;
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
                    SourceItemInterface::QUANTITY => $product['qty'] ?? null,
                    SourceItemInterface::STATUS => $product['is_in_stock'] ?? 0,
                ];
            }
        }
        $adapter->getConnection()->insertOnDuplicate(
            $adapter->getConnection()->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM),
            $data
        );
    }
}
