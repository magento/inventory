<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\InventoryApi\Api\StockRepositoryInterface;

/**
 * Add stock list for stock list dropdown in "Advanced Inventory" panel.
 */
class AddStockList extends AbstractModifier
{
    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @param StockRepositoryInterface $stockRepository
     */
    public function __construct(StockRepositoryInterface $stockRepository)
    {
        $this->stockRepository = $stockRepository;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        $stocks = $this->stockRepository->getList();
        $data = [];
        foreach ($stocks->getItems() as $stock) {
            $data[] = [
                'value' => (int)$stock->getStockId(),
                'label' => (string)$stock->getName(),
            ];
        }
        $meta['advanced_inventory_modal']['children']['stock_data']['children']['stock_list']
        ['arguments']['data']['config']['list'] = $data;

        return $meta;
    }
}