<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model;

use Magento\Bundle\Api\Data\OptionInterface;
use Magento\Bundle\Model\OptionRepository;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestInterfaceFactory;

/**
 * Get bundle product stock status service.
 */
class GetBundleProductStockStatus
{
    /**
     * @var OptionRepository
     */
    private $optionRepository;

    /**
     * @var GetProductSelection
     */
    private $getProductSelection;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalableForRequestedQty;

    /**
     * @var IsProductSalableForRequestedQtyRequestInterfaceFactory
     */
    private $isProductSalableForRequestedQtyRequestFactory;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @param OptionRepository $optionRepository
     * @param GetProductSelection $getProductSelection
     * @param AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty
     * @param IsProductSalableForRequestedQtyRequestInterfaceFactory $isProductSalableForRequestedQtyRequestFactory
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     */
    public function __construct(
        OptionRepository $optionRepository,
        GetProductSelection $getProductSelection,
        AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty,
        IsProductSalableForRequestedQtyRequestInterfaceFactory $isProductSalableForRequestedQtyRequestFactory,
        GetStockItemConfigurationInterface $getStockItemConfiguration
    ) {
        $this->optionRepository = $optionRepository;
        $this->getProductSelection = $getProductSelection;
        $this->areProductsSalableForRequestedQty = $areProductsSalableForRequestedQty;
        $this->isProductSalableForRequestedQtyRequestFactory = $isProductSalableForRequestedQtyRequestFactory;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
    }

    /**
     * Provides bundle product stock status.
     *
     * @param ProductInterface $product
     * @param OptionInterface[] $bundleOptions
     * @param int $stockId
     *
     * @return bool
     * @throws LocalizedException
     * @throws SkuIsNotAssignedToStockException
     */
    public function execute(ProductInterface $product, array $bundleOptions, int $stockId): bool
    {
        $isSalable = false;
        foreach ($bundleOptions as $option) {
            $hasSalable = false;
            $bundleSelections = $this->getProductSelection->execute($product, $option);
            $skuRequests = [];
            foreach ($bundleSelections->getItems() as $selection) {
                if ((int)$selection->getStatus() === Status::STATUS_ENABLED) {
                    $skuRequests[] = $this->isProductSalableForRequestedQtyRequestFactory->create(
                        [
                            'sku' => (string)$selection->getSku(),
                            'qty' => $this->getRequestedQty($selection, $stockId),
                        ]
                    );
                }
            }
            $results = $this->areProductsSalableForRequestedQty->execute($skuRequests, $stockId);
            foreach ($results as $result) {
                if ($result->isSalable()) {
                    $hasSalable = true;
                    break;
                }
            }
            if ($hasSalable) {
                $isSalable = true;
            }

            if (!$hasSalable && $option->getRequired()) {
                $isSalable = false;
                break;
            }
        }

        return $isSalable;
    }

    /**
     * Get bundle product selection qty.
     *
     * @param Product $product
     * @param int $stockId
     * @return float
     * @throws LocalizedException
     * @throws SkuIsNotAssignedToStockException
     */
    private function getRequestedQty(Product $product, int $stockId): float
    {
        if ((int)$product->getSelectionCanChangeQty()) {
            $stockItemConfiguration = $this->getStockItemConfiguration->execute((string)$product->getSku(), $stockId);
            return $stockItemConfiguration->getMinSaleQty();
        }

        return (float)$product->getSelectionQty();
    }
}
