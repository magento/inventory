<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\CatalogInventory\Ui\DataProvider\Product\Form\Modifier\AdvancedInventory;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\InventoryCatalog\Model\DefaultSourceProvider;
use Magento\InventoryCatalog\Model\IsSingleSourceModeInterface;
use Magento\InventoryLowQuantityNotification\Model\SourceItemConfiguration\Command\Get;

/**
 * Hide notify_stock_qty field in Advanced Inventory panel for multi stock.
 *
 * Read notify_stock_qty field from inventory_low_stock_notification_configuration in Advanced Inventory panel for
 * single stock.
 */
class NotifyStockQty extends AbstractModifier
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var Get
     */
    private $get;

    /**
     * @var DefaultSourceProvider
     */
    private $defaultSourceProvider;

    /**
     * @param ArrayManager $arrayManager
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param LocatorInterface $locator
     * @param Get $get
     * @param DefaultSourceProvider $defaultSourceProvider
     */
    public function __construct(
        ArrayManager $arrayManager,
        IsSingleSourceModeInterface $isSingleSourceMode,
        LocatorInterface $locator,
        Get $get,
        DefaultSourceProvider $defaultSourceProvider
    ) {
        $this->arrayManager = $arrayManager;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->locator = $locator;
        $this->get = $get;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        if (false === $this->isSingleSourceMode->execute()) {
            return $data;
        }

        $product = $this->locator->getProduct();
        $stockData = $data[$product->getId()][self::DATA_SOURCE_DEFAULT][AdvancedInventory::STOCK_DATA_FIELDS];

        if (null === $stockData || !$product->getSku()) {
            return $data;
        }

        $sourceItemConfiguration = $this->get->execute($this->defaultSourceProvider->getCode(), $product->getSku());
        if (null == $sourceItemConfiguration->getNotifyStockQty()) {
            $stockData['use_config_notify_stock_qty'] = '1';
        } else {
            $stockData['use_config_notify_stock_qty'] = '0';
            $stockData['notify_stock_qty'] = $sourceItemConfiguration->getNotifyStockQty();
        }

        $data[$product->getId()][self::DATA_SOURCE_DEFAULT][AdvancedInventory::STOCK_DATA_FIELDS] = $stockData;

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        if (true === $this->isSingleSourceMode->execute()) {
            return $meta;
        }

        $stockDataPath = $this->arrayManager->findPath(
            AdvancedInventory::STOCK_DATA_FIELDS,
            $meta,
            null,
            'children'
        );
        if (null === $stockDataPath) {
            return $meta;
        }

        $notifyQtyPath = $stockDataPath . '/children/container_notify_stock_qty/arguments/data/config';
        $meta = $this->arrayManager->set(
            $notifyQtyPath,
            $meta,
            [
                'visible' => 0,
                'imports' => '',
            ]
        );

        return $meta;
    }
}
