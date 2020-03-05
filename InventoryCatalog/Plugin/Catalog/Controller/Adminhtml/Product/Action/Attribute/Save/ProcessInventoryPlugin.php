<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save;

use Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save;
use Magento\Catalog\Helper\Product\Edit\Action\Attribute;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\InventoryCatalog\Model\UpdateInventory\InventoryDataFactory;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;

/**
 * Process source items on mass product attribute update plugin.
 */
class ProcessInventoryPlugin
{
    /**
     * @var Attribute
     */
    private $attributeHelper;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var InventoryDataFactory
     */
    private $inventoryDataForUpdateFactory;

    /**
     * @var SerializerInterface
     */
    private $serialize;

    /**
     * @param Attribute $attributeHelper
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param StockConfigurationInterface $stockConfiguration
     * @param InventoryDataFactory $inventoryDataForUpdateFactory
     * @param SerializerInterface $serialize
     * @param PublisherInterface $publisher
     */
    public function __construct(
        Attribute $attributeHelper,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        StockConfigurationInterface $stockConfiguration,
        InventoryDataFactory $inventoryDataForUpdateFactory,
        SerializerInterface $serialize,
        PublisherInterface $publisher
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->stockConfiguration = $stockConfiguration;
        $this->inventoryDataForUpdateFactory = $inventoryDataForUpdateFactory;
        $this->publisher = $publisher;
        $this->serialize = $serialize;
    }

    /**
     * Asynchronously process legacy stock items.
     *
     * @param Save $subject
     *
     * @param ResultInterface $result
     * @return ResultInterface
     */
    public function afterExecute(Save $subject, ResultInterface $result)
    {
        $request = $subject->getRequest();
        $inventoryData = $this->addConfigSettings($request->getParam('inventory', []));
        try {
            $skus = $this->getSkusByProductIds->execute($this->attributeHelper->getProductIds());
        } catch (NoSuchEntityException $e) {
            $skus = [];
        }

        if ($inventoryData && $skus) {
            $inventoryData = $this->inventoryDataForUpdateFactory->create(
                [
                    'skus' => $skus,
                    'data' => $this->serialize->serialize($inventoryData),
                ]
            );
            $this->publisher->publish('inventory.mass.update', $inventoryData);
        }

        return $result;
    }

    /**
     * Add config settings to inventory data.
     *
     * @param array $inventoryData
     * @return array
     */
    private function addConfigSettings(array $inventoryData): array
    {
        $options = $this->stockConfiguration->getConfigItemOptions();
        foreach ($options as $option) {
            $useConfig = 'use_config_' . $option;
            if (isset($inventoryData[$option]) && !isset($inventoryData[$useConfig])) {
                $inventoryData[$useConfig] = 0;
            }
        }

        return $inventoryData;
    }
}
