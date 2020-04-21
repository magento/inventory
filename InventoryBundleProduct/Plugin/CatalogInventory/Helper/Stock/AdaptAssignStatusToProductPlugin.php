<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Plugin\CatalogInventory\Helper\Stock;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryBundleProduct\Model\GetBundleProductStockStatus;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Process bundle product stock status considering bundle selections salable status.
 */
class AdaptAssignStatusToProductPlugin
{
    /**
     * @var Type
     */
    private $bundleProductType;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var GetBundleProductStockStatus
     */
    private $getBundleProductStockStatus;

    /**
     * @param Type $bundleProductType
     * @param GetBundleProductStockStatus $getBundleProductStockStatus
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        Type $bundleProductType,
        GetBundleProductStockStatus $getBundleProductStockStatus,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver
    ) {
        $this->bundleProductType = $bundleProductType;
        $this->getBundleProductStockStatus = $getBundleProductStockStatus;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
    }

    /**
     * Process bundle product stock status, considering bundle selections.
     *
     * @param Stock $subject
     * @param Product $product
     * @param int|null $status
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAssignStatusToProduct(
        Stock $subject,
        Product $product,
        $status = null
    ): array {
        if ($product->getTypeId() === Type::TYPE_CODE) {
            $website = $this->storeManager->getWebsite();
            $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
            $options = $this->bundleProductType->getOptionsCollection($product);
            try {
                $status = (int)$this->getBundleProductStockStatus->execute(
                    $product,
                    $options->getItems(),
                    $stock->getStockId()
                );
            } catch (LocalizedException $e) {
                $status = 0;
            }
        }

        return [$product, $status];
    }
}
