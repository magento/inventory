<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\StockStateProvider;

use Magento\Catalog\Model\Product\Type;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySales\Model\GetBackorder;

/**
 * Replace legacy quote item check
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckQuoteItemQtyPlugin
{
    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var GetBackorder
     */
    private $getBackorder;

    /**
     * @param ObjectFactory|null $objectFactory
     * @param GetProductTypesBySkusInterface|null $getProductTypesBySkus
     * @param GetSkusByProductIdsInterface|null $getSkusByProductIds
     * @param GetBackorder|null $getBackorder
     */
    public function __construct(
        ?ObjectFactory $objectFactory = null,
        ?GetProductTypesBySkusInterface $getProductTypesBySkus = null,
        ?GetSkusByProductIdsInterface $getSkusByProductIds = null,
        ?GetBackorder $getBackorder = null
    ) {
        $this->objectFactory = $objectFactory
            ?? ObjectManager::getInstance()->get(ObjectFactory::class);
        $this->getProductTypesBySkus = $getProductTypesBySkus
            ?? ObjectManager::getInstance()->get(GetProductTypesBySkusInterface::class);
        $this->getSkusByProductIds = $getSkusByProductIds
            ?? ObjectManager::getInstance()->get(GetSkusByProductIdsInterface::class);
        $this->getBackorder = $getBackorder
            ?? ObjectManager::getInstance()->get(GetBackorder::class);
    }

    /**
     * Replace legacy quote item check
     *
     * @param StockStateProviderInterface $subject
     * @param \Closure $proceed
     * @param StockItemInterface $stockItem
     * @param int|float $itemQty
     * @param int|float $qtyToCheck
     * @param int|float $origQty
     *
     * @return DataObject
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCheckQuoteItemQty(
        StockStateProviderInterface $subject,
        \Closure $proceed,
        StockItemInterface $stockItem,
        $itemQty,
        $qtyToCheck,
        $origQty = 0
    ) {
        $productId = $stockItem->getProductId();
        $skus = $this->getSkusByProductIds->execute([$productId]);
        $productSku = $skus[$productId];
        $productType = $this->getProductTypesBySkus->execute([$productSku])[$productSku];
        if ($productType !== Type::TYPE_SIMPLE) {
            $result = $this->objectFactory->create();
            $result->setHasError(false);
            return $result;
        }
        return $this->getBackorder->execute((int) $productId, $itemQty, $qtyToCheck, null);
    }
}
