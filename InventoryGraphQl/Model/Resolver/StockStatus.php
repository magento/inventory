<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryGraphQl\Model\Resolver;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Resolve the stock status of all products of a single response with one salability check.
 */
class StockStatus implements BatchResolverInterface
{
    private const IN_STOCK = "IN_STOCK";
    private const OUT_OF_STOCK = "OUT_OF_STOCK";

    /**
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     * @param AreProductsSalableInterface $areProductsSalable
     * @param StockStatusProvider $stockStatusProvider
     * @param ValueFactory $valueFactory
     */
    public function __construct(
        private readonly GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        private readonly AreProductsSalableInterface $areProductsSalable,
        private readonly StockStatusProvider $stockStatusProvider,
        private readonly ValueFactory $valueFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(ContextInterface $context, Field $field, array $requests): BatchResponse
    {
        $skus = [];
        foreach ($requests as $key => $request) {
            $sku = $this->getBatchableSku($request);
            if ($sku !== null) {
                $skus[$key] = $sku;
            }
        }

        $response = new BatchResponse();
        try {
            $salability = $skus ? $this->getSalability($skus) : [];
        } catch (\Throwable $e) {
            $salability = [];
        }
        foreach ($requests as $key => $request) {
            if (!array_key_exists($key, $salability)) {
                $response->addResponse($request, $this->resolveSingle($field, $context, $request));
                continue;
            }

            $response->addResponse($request, $salability[$key] ? self::IN_STOCK : self::OUT_OF_STOCK);
        }

        return $response;
    }

    /**
     * Resolve one request with the single product resolver; its error is reported for that request only
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param BatchRequestItemInterface $request
     * @return mixed
     */
    private function resolveSingle(Field $field, ContextInterface $context, BatchRequestItemInterface $request): mixed
    {
        try {
            return $this->stockStatusProvider->resolve(
                $field,
                $context,
                $request->getInfo(),
                $request->getValue(),
                $request->getArgs()
            );
        } catch (\Throwable $e) {
            return $this->valueFactory->create(
                static function () use ($e) {
                    throw $e;
                }
            );
        }
    }

    /**
     * Get the SKU to check in the batch, or null when the single product resolver has to handle the request
     *
     * @param BatchRequestItemInterface $request
     * @return string|null
     */
    private function getBatchableSku(BatchRequestItemInterface $request): ?string
    {
        try {
            $value = $request->getValue() ?? [];
            $product = $value['model'] ?? null;
            if (!$product instanceof ProductInterface || $this->isBundleToBeBought($product)) {
                return null;
            }
            if (empty($product->getOptions())) {
                $sku = $product->getSku();
            } else {
                $sku = array_key_exists('sku', $value) ? $value['sku'] : null;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return is_string($sku) ? $sku : null;
    }

    /**
     * Get the salability of each requested SKU, keyed like the given SKUs; SKUs without a result are left out
     *
     * @param array $skus
     * @return bool[]
     */
    private function getSalability(array $skus): array
    {
        $stockId = $this->getStockIdForCurrentWebsite->execute();
        $uniqueSkus = array_values(array_unique($skus));
        $salableBySku = [];
        foreach ($this->areProductsSalable->execute($uniqueSkus, $stockId) as $result) {
            $salableBySku[(string)$result->getSku()] = $result->isSalable();
        }

        $salability = [];
        $unresolved = [];
        foreach ($skus as $key => $sku) {
            if (isset($unresolved[$sku])) {
                continue;
            }
            // A multi-SKU lookup can leave out SKUs that a single-SKU lookup still resolves through its fallback.
            if (!array_key_exists($sku, $salableBySku)) {
                try {
                    $result = current($this->areProductsSalable->execute([$sku], $stockId));
                } catch (\Throwable $e) {
                    $result = false;
                }
                if (!$result) {
                    $unresolved[$sku] = true;
                    continue;
                }
                $salableBySku[$sku] = $result->isSalable();
            }
            $salability[$key] = $salableBySku[$sku];
        }

        return $salability;
    }

    /**
     * Bundles intended to be bought are checked by their selections and keep the single product resolver
     *
     * @param ProductInterface $product
     * @return bool
     */
    private function isBundleToBeBought(ProductInterface $product): bool
    {
        return $product->getTypeId() === Type::TYPE_CODE && $product->getCustomOption('info_buyRequest');
    }
}
