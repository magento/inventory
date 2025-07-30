<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryGraphQl\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\Bundle\Model\Product\Type;

/**
 * @inheritdoc
 */
class StockStatusProvider implements ResolverInterface
{
    private const IN_STOCK = "IN_STOCK";
    private const OUT_OF_STOCK = "OUT_OF_STOCK";

    /**
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     * @param AreProductsSalableInterface $areProductsSalable
     * @param Type $bundleType
     */
    public function __construct(
        private readonly GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        private readonly AreProductsSalableInterface $areProductsSalable,
        private readonly Type $bundleType
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        if (!array_key_exists('model', $value) || !$value['model'] instanceof ProductInterface) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $product = $value['model'];

        if ($product->getTypeId() === Type::TYPE_CODE) {
            try {
                if (!$product->getCustomOption('bundle_selection_ids')) {
                    return self::OUT_OF_STOCK;
                }
                $this->bundleType->checkProductBuyState($product);
            } catch (LocalizedException $e) {
                return self::OUT_OF_STOCK;
            }

            return self::IN_STOCK;
        }

        $productSku = (!empty($product->getOptions())) ? $value['sku'] : $product->getSku();
        $stockId = $this->getStockIdForCurrentWebsite->execute();
        $result = $this->areProductsSalable->execute([$productSku], $stockId);

        return current($result)->isSalable() ? self::IN_STOCK : self::OUT_OF_STOCK;
    }
}
