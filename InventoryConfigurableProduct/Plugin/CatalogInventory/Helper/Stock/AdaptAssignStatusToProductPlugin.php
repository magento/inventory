<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\Helper\Stock;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Process configurable product stock status considering configurable options salable status.
 */
class AdaptAssignStatusToProductPlugin
{
    /**
     * @var Configurable
     */
    private $configurable;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @param Configurable $configurable
     * @param AreProductsSalableInterface $areProductsSalable
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param GetStockItemDataInterface $getStockItemData
     */
    public function __construct(
        Configurable $configurable,
        AreProductsSalableInterface $areProductsSalable,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        GetStockItemDataInterface $getStockItemData
    ) {
        $this->configurable = $configurable;
        $this->areProductsSalable = $areProductsSalable;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->getStockItemData = $getStockItemData;
    }

    /**
     * Process configurable product stock status, considering configurable options.
     *
     * @param Stock $subject
     * @param Product $product
     * @param int|null $status
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAssignStatusToProduct(
        Stock $subject,
        Product $product,
        $status = null
    ): array {
        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            $website = $this->storeManager->getWebsite();
            $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
            $stockId = $stock->getStockId();
            try {
                $stockItemData = $this->getStockItemData->execute($product->getSku(), $stockId);
            } catch (NoSuchEntityException $exception) {
                $stockItemData = null;
            }
            if (null !== $stockItemData) {
                if (!((bool) $stockItemData[GetStockItemDataInterface::IS_SALABLE])) {
                    return [$product, $status];
                }
            }
            $options = $this->configurable->getConfigurableOptions($product);
            $status = 0;
            $skus = [[]];
            foreach ($options as $attribute) {
                $skus[] = array_column($attribute, 'sku');
            }
            $skus = array_merge(...$skus);
            $results = $this->areProductsSalable->execute($skus, $stock->getStockId());
            foreach ($results as $result) {
                if ($result->isSalable()) {
                    $status = 1;
                    break;
                }
            }
        }

        return [$product, $status];
    }
}
