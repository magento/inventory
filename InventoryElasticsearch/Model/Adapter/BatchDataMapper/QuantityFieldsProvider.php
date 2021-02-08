<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Model\Adapter\BatchDataMapper;

use Magento\AdvancedSearch\Model\Adapter\DataMapper\AdditionalFieldsProviderInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Provide data mapping for quantity_and_stock_status fields
 */
class QuantityFieldsProvider implements AdditionalFieldsProviderInterface
{
    private const ATTRIBUTE_CODE = 'quantity_and_stock_status';

    /**
     * @var array|null
     */
    private $data;

    /**
     * @var AttributeProvider
     */
    private $attributeAdapterProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param AttributeProvider $attributeAdapterProvider
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param StockResolverInterface $stockResolver
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param LoggerInterface $logger
     */
    public function __construct(
        AttributeProvider $attributeAdapterProvider,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        StockResolverInterface $stockResolver,
        GetProductSalableQtyInterface $getProductSalableQty,
        LoggerInterface $logger
    ) {
        $this->attributeAdapterProvider = $attributeAdapterProvider;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->stockResolver = $stockResolver;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->logger = $logger;
    }

    /**
     * Get quantity_and_stock_status fields for data mapper
     *
     * @param array $productIds
     * @param int $storeId
     * @return array
     */
    public function getFields(array $productIds, $storeId)
    {
        $fields = [];
        $attribute = $this->attributeAdapterProvider->getByAttributeCode(self::ATTRIBUTE_CODE);
        if (!$attribute->isSortable()) {
            return $fields;
        }

        $stockId = $this->getStockIdByStore((int) $storeId);
        foreach ($productIds as $productId) {
            try {
                $product = $this->productRepository->getById($productId);
                $fields[$productId][self::ATTRIBUTE_CODE] = $this->getQuantityByStockSku($stockId, $product->getSku());
            } catch (\Exception $e) {
                $this->logger->error($e);
            }
        }

        return $fields;
    }

    /**
     * Returns stockId by storeId
     *
     * @param int $storeId
     * @return int
     */
    private function getStockIdByStore(int $storeId): int
    {
        $websiteId = $this->storeManager->getStore($storeId)
            ->getWebsiteId();
        $website = $this->storeManager->getWebsite($websiteId);
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());

        return (int) $stock->getStockId();
    }

    /**
     * Returns product quantity in stock by sku
     *
     * @param int $stockId
     * @param string $sku
     * @return float
     */
    private function getQuantityByStockSku(int $stockId, string $sku): float
    {
        if (!isset($this->data[$stockId][$sku])) {
            $this->data[$stockId][$sku] = $this->getProductSalableQty->execute($sku, $stockId);
        }

        return $this->data[$stockId][$sku];
    }
}
