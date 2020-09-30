<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Plugin\InventorySales;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryBundleProduct\Model\GetBundleProductStockStatus;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventorySales\Model\IsProductSalableCondition\IsProductSalableConditionChain;

/**
 * Check if bundle product is salable with bundle options.
 */
class IsBundleProductSalable
{
    /**
     * @var Type
     */
    private $bundleProductType;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var GetBundleProductStockStatus
     */
    private $getBundleProductStockStatus;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @param Type $type
     * @param ProductRepositoryInterface $repository
     * @param GetBundleProductStockStatus $getBundleProductStockStatus
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     */
    public function __construct(
        Type $type,
        ProductRepositoryInterface $repository,
        GetBundleProductStockStatus $getBundleProductStockStatus,
        GetProductTypesBySkusInterface $getProductTypesBySkus
    ) {
        $this->bundleProductType = $type;
        $this->productRepository = $repository;
        $this->getBundleProductStockStatus = $getBundleProductStockStatus;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
    }

    /**
     * Get bundle product status based on child statuses.
     *
     * @param IsProductSalableConditionChain $subject
     * @param \Closure $proceed
     * @param string $sku
     * @param int $stockId
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        IsProductSalableConditionChain $subject,
        \Closure $proceed,
        string $sku,
        int $stockId
    ): bool {
        try {
            $types = $this->getProductTypesBySkus->execute([$sku]);

            $isProductSalable = $proceed($sku, $stockId);
            if (!isset($types[$sku]) || $types[$sku] !== Type::TYPE_CODE || !$isProductSalable) {
                return $isProductSalable;
            }

            // TODO: remove in https://github.com/magento/inventory/issues/3201
            // Product salability MUST NOT BE CALLED during product load.
            // Tests stabilization.
            /** @var \Magento\Framework\Registry $registry */
            $registry = ObjectManager::getInstance()->get(\Magento\Framework\Registry::class);
            $key = 'inventory_check_product' . $sku;

            if ($registry->registry($key)) {
                $product = $registry->registry($key);
            } else {
                $product = $this->productRepository->get($sku);
            }

            /** @noinspection PhpParamsInspection */
            $options = $this->bundleProductType->getOptionsCollection($product);
            $status = $this->getBundleProductStockStatus->execute(
                $product,
                $options->getItems(),
                $stockId
            );
        } catch (LocalizedException $e) {
            $status = false;
        }

        return $status;
    }
}
