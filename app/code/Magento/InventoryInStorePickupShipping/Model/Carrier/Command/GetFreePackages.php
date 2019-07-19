<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Model\Carrier\Command;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\Command\GetFreePackagesInterface;
use Magento\Quote\Model\Quote\Address\Item as ItemAlias;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * @inheritdoc
 */
class GetFreePackages implements GetFreePackagesInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute(RateRequest $request): float
    {
        $freeBoxes = 0.0;
        if ($request->getAllItems()) {
            /** @var QuoteItem|ItemAlias $item */
            foreach ($request->getAllItems() as $item) {
                /** @var Product $product */
                $product = $this->productRepository->get($item->getSku(), false, $item->getStoreId());
                if ($product->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    $freeBoxes += $this->getFreeBoxesCountFromChildren($item);
                } elseif ($item->getFreeShipping()) {
                    $freeBoxes += $item->getQty();
                }
            }
        }

        return $freeBoxes;
    }

    /**
     * Check if item is eligible for free shipping.
     *
     * @param AbstractItem $item
     * @return float
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getFreeBoxesCountFromChildren(AbstractItem $item): float
    {
        $freeBoxes = 0.0;
        /** @var QuoteItem|ItemAlias $child */
        foreach ($item->getChildren() as $child) {
            /** @var Product $product */
            $product = $this->productRepository->get($child->getSku(), false, $child->getStoreId());
            if ($child->getFreeShipping() && !$product->isVirtual()) {
                $freeBoxes += $item->getQty() * $child->getQty();
            }
        }

        return $freeBoxes;
    }
}
