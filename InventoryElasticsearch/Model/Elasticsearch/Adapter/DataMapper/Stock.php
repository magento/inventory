<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Model\Elasticsearch\Adapter\DataMapper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryElasticsearch\Model\ResourceModel\Inventory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Stock for mapping
 */
class Stock
{
    /**
     * @var Inventory
     */
    private $inventory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Stock constructor.
     * @param Inventory $inventory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Inventory $inventory,
        StoreManagerInterface $storeManager
    ) {
        $this->inventory = $inventory;
        $this->storeManager = $storeManager;
    }

    /**
     * Map the product attribute with it's sku value
     *
     * @param array $documents
     * @param mixed $storeId
     * @return array
     * @throws NoSuchEntityException
     */
    public function map(array $documents, mixed $storeId): array
    {
        $productStockStatus = $this->inventory->getStockStatus(
            $this->storeManager->getStore($storeId)->getWebsite()->getCode()
        );

        if (!empty($productStockStatus)) {
            foreach ($documents as $productId => $document) {
                $sku = $document['sku'] ?? '';
                $document['is_out_of_stock'] = !$sku ? 1 : (int)($productStockStatus[$sku] ?? 1);
                $documents[$productId] = $document;
            }
        }

        return $documents;
    }
}
