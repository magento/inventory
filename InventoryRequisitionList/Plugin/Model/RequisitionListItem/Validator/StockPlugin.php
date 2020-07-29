<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryRequisitionList\Plugin\Model\RequisitionListItem\Validator;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestInterfaceFactory;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Model\RequisitionListItem\Validator\Stock;

/**
 * This plugin adds multi-source stock calculation capabilities to the Requisition List feature.
 */
class StockPlugin
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteId;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var AreProductsSalableForRequestedQtyInterface
     */
    private $areProductsSalableForRequestedQty;

    /**
     * @var IsProductSalableForRequestedQtyRequestInterfaceFactory
     */
    private $isProductSalableForRequestedQtyRequestFactory;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteId
     * @param AreProductsSalableInterface $areProductsSalable
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param AreProductsSalableForRequestedQtyInterface $isProductSalableForRequestedQty
     * @param IsProductSalableForRequestedQtyRequestInterfaceFactory $isProductSalableForRequestedQtyRequestFactory
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        StockByWebsiteIdResolverInterface $stockByWebsiteId,
        AreProductsSalableInterface $areProductsSalable,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        AreProductsSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
        IsProductSalableForRequestedQtyRequestInterfaceFactory $isProductSalableForRequestedQtyRequestFactory
    ) {
        $this->productRepository = $productRepository;
        $this->stockByWebsiteId = $stockByWebsiteId;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->areProductsSalable = $areProductsSalable;
        $this->areProductsSalableForRequestedQty = $isProductSalableForRequestedQty;
        $this->isProductSalableForRequestedQtyRequestFactory = $isProductSalableForRequestedQtyRequestFactory;
    }

    /**
     * Extend requisition list item stock validation with multi-sourcing capabilities.
     *
     * @param Stock $subject
     * @param callable $proceed
     * @param RequisitionListItemInterface $item
     * @return array Item errors
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function aroundValidate(Stock $subject, callable $proceed, RequisitionListItemInterface $item)
    {
        $errors = [];
        $product = $this->productRepository->get($item->getSku(), false, null, true);

        if (!$this->isSourceItemManagementAllowedForProductType->execute($product->getTypeId())) {
            return $proceed($item);
        }

        $websiteId = (int)$product->getStore()->getWebsiteId();
        $stockId = (int)$this->stockByWebsiteId->execute($websiteId)->getStockId();
        $result = $this->areProductsSalable->execute([$product->getSku()], $stockId);
        $result = current($result);
        $isSalable = $result->isSalable();

        if (!$isSalable) {
            $errors[$subject::ERROR_OUT_OF_STOCK] = __('The SKU is out of stock.');
            return $errors;
        }
        $request = $this->isProductSalableForRequestedQtyRequestFactory->create(
            [
                'sku' => $product->getSku(),
                'qty' => (float)$item->getQty(),
            ]
        );
        $result = $this->areProductsSalableForRequestedQty->execute([$request], $stockId);
        $result = current($result);
        if (!$result->isSalable() && !$product->isComposite()) {
            $errors[$subject::ERROR_LOW_QUANTITY] = __('The requested qty is not available');
            return $errors;
        }

        return $errors;
    }
}
