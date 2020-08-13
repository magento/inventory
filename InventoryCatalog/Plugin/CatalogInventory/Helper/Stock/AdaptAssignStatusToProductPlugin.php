<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Helper\Stock;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Adapt assignStatusToProduct for multi stocks.
 */
class AdaptAssignStatusToProductPlugin
{
    /**
     * @var GetStockIdForCurrentWebsite
     */
    private $getStockIdForCurrentWebsite;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     * @param AreProductsSalableInterface $areProductsSalable
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        AreProductsSalableInterface $areProductsSalable,
        DefaultStockProviderInterface $defaultStockProvider,
        GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
        $this->areProductsSalable = $areProductsSalable;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
    }

    /**
     * Assign stock status to product considering multi stock environment.
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
        ?int $status = null
    ): array {
        if (null === $product->getSku()) {
            return [$product, $status];
        }

        try {
            // @TODO VERY temporary solution until https://github.com/magento/inventory/pull/3039 is resolved
            // Product salability MUST NOT BE CALLED during product load.
            // Tests stabilization.
            /** @var \Magento\Framework\Registry $registry */
            $registry = ObjectManager::getInstance()->get(\Magento\Framework\Registry::class);
            $key = 'inventory_check_product' . $product->getSku();
            if ($registry->registry($key)) {
                $registry->unregister($key);
            }
            $registry->register($key, $product);

            $this->getProductIdsBySkus->execute([$product->getSku()]);
            if (null === $status) {
                $stockId = $this->getStockIdForCurrentWebsite->execute();
                $result = $this->areProductsSalable->execute([$product->getSku()], $stockId);
                $result = current($result);
                $registry->unregister($key);
                return [$product, (int)$result->isSalable()];
            }
            $registry->unregister($key);
        } catch (NoSuchEntityException $e) {
            $registry->unregister($key);
            return [$product, $status];
        }
        return [$product, $status];
    }
}
