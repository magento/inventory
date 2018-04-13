<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Ui\DataProvider\Source\Listing\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\CatalogInventory\Model\Source\StockConfiguration;

/**
 * Notify stock quantity modifier. To add default value for "notify_stock_qty_use_default" and "notify_stock_qty"
 * when map it from source modal window in product edit page to source grid.
 */
class NotifyStockQuantity extends AbstractModifier
{
    /**
     * @var StockConfiguration
     */
    private $stockConfiguration;

    /**
     * @param StockConfiguration $stockConfiguration
     */
    public function __construct(StockConfiguration $stockConfiguration)
    {
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        if ($data['totalRecords'] > 0) {
            foreach ($data['items'] as &$item) {
                $item['notify_stock_qty_use_default'] = '1';
                $item['notify_stock_qty'] = $this->stockConfiguration->getValue('notify_stock_qty');
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }
}
