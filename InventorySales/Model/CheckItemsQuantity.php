<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterface;
use Magento\InventorySalesApi\Api\Data\SkuQtyRequestInterfaceFactory;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;

/**
 * Verify items are salable for requested quantity.
 */
class CheckItemsQuantity
{
    /**
     * @var AreProductsSalableForRequestedQtyInterface
     */
    private $areProductsSalableForRequestedQty;

    /**
     * @var SkuQtyRequestInterfaceFactory
     */
    private $skuQtyRequestFactory;

    /**
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty @deprecated
     * @param SkuQtyRequestInterfaceFactory|null $skuQtyRequestFactory
     * @param AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
        SkuQtyRequestInterfaceFactory $skuQtyRequestFactory = null,
        AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty = null
    ) {
        $this->areProductsSalableForRequestedQty = $areProductsSalableForRequestedQty ?: ObjectManager::getInstance()
            ->get(AreProductsSalableForRequestedQtyInterface::class);
        $this->skuQtyRequestFactory = $skuQtyRequestFactory ?: ObjectManager::getInstance()
            ->get(SkuQtyRequestInterfaceFactory::class);
    }

    /**
     * Check whether all items salable
     *
     * @param array $items ['sku' => 'qty', ...]
     * @param int $stockId
     * @return void
     * @throws LocalizedException
     */
    public function execute(array $items, int $stockId): void
    {
        $skuRequests = [];
        foreach ($items as $sku => $qty) {
            $skuRequests[] = $this->skuQtyRequestFactory->create(['sku' => $sku, 'qty' => $qty]);
        }
        $result = $this->areProductsSalableForRequestedQty->execute($skuRequests, $stockId);
        foreach ($result->getSalable() as $isSalable) {
            if (false === $isSalable->isSalable()) {
                $errors = $isSalable->getErrors();
                /** @var ProductSalabilityErrorInterface $errorMessage */
                $errorMessage = array_pop($errors);
                throw new LocalizedException(__($errorMessage->getMessage()));
            }
        }
    }
}
