<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\Block\Product\View\Type;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as Subject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventoryCatalog\Model\GetProductQtyById;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;

/**
 * Add option qty.
 */
class AddOptionQtysPlugin
{
    /**
     * @var StockItemConfigurationInterface
     */
    private $stockItemConfig;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var GetProductQtyById
     */
    private $getProductQtyById;

    /**
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @param Json $jsonSerializer
     * @param GetProductQtyById $getProductQtyById
     */
    public function __construct(
        StockItemConfigurationInterface $stockItemConfiguration,
        Json $jsonSerializer,
        GetProductQtyById $getProductQtyById
    ) {
        $this->stockItemConfig = $stockItemConfiguration;
        $this->jsonSerializer = $jsonSerializer;
        $this->getProductQtyById = $getProductQtyById;
    }

    /**
     * Composes configuration for js
     *
     * @param Subject $configurable
     * @param string $config
     * @return string
     */
    public function afterGetJsonConfig(
        Subject $configurable,
        string $config
    ): string {
        $config = $this->jsonSerializer->unserialize($config);
        $config['optionQtys'] = $this->getOptionQtys($configurable);
        return $this->jsonSerializer->serialize($config);
    }

    /**
     * Get option qty.
     *
     * @return array
     */
    private function getOptionQtys(Subject $configurable): array
    {
        $qtys = [];
        foreach ($configurable->getAllowProducts() as $product) {
            if ($this->useQtyForViewing($product)) {
                $qtys[$product->getId()] = $this->getStockQtyLeft($product);
            }
        }
        return $qtys;
    }

    /**
     * Use qty for viewing.
     *
     * @param Product $product
     * @return bool
     */
    private function useQtyForViewing(Product $product): bool
    {
        $productSalableQty = $this->getProductQtyById->execute($product->getId());
        return ($this->stockItemConfig->getBackorders() === StockItemConfigurationInterface::BACKORDERS_NO
            || $this->stockItemConfig->getBackorders() !== StockItemConfigurationInterface::BACKORDERS_NO
            && $this->stockItemConfig->getMinQty() < 0)
            && $productSalableQty <= $this->stockItemConfig->getStockThresholdQty()
            && $productSalableQty > 0;
    }

    /**
     * Get stock qty left.
     *
     * @param Product $product
     * @return float
     */
    private function getStockQtyLeft(Product $product): float
    {
        return (float)($this->getProductQtyById->execute($product->getId()) -
            $this->stockItemConfig->getMinQty());
    }
}
