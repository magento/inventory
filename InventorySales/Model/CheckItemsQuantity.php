<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterface;
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
     * @var IsProductSalableForRequestedQtyRequestInterfaceFactory
     */
    private $isProductSalableForRequestedQtyRequestFactory;

    /**
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty @deprecated
     * @param IsProductSalableForRequestedQtyRequestInterfaceFactory|null $isProductSalableForRequestedQtyRequestFactory
     * @param AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
        ?IsProductSalableForRequestedQtyRequestInterfaceFactory $isProductSalableForRequestedQtyRequestFactory = null,
        ?AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty = null
    ) {
        $this->areProductsSalableForRequestedQty = $areProductsSalableForRequestedQty ?: ObjectManager::getInstance()
            ->get(AreProductsSalableForRequestedQtyInterface::class);
        $this->isProductSalableForRequestedQtyRequestFactory = $isProductSalableForRequestedQtyRequestFactory
            ?: ObjectManager::getInstance()->get(IsProductSalableForRequestedQtyRequestInterfaceFactory::class);
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
            $skuRequests[] = $this->isProductSalableForRequestedQtyRequestFactory->create(
                [
                    'sku' => $sku,
                    'qty' => $qty,
                ]
            );
        }
        $results = $this->areProductsSalableForRequestedQty->execute($skuRequests, $stockId);
        foreach ($results as $result) {
            if (false === $result->isSalable()) {
                $errors = $result->getErrors();
                /** @var ProductSalabilityErrorInterface $errorMessage */
                $errorMessage = array_pop($errors);
                throw new LocalizedException(__($errorMessage->getMessage()));
            }
        }
    }
}
