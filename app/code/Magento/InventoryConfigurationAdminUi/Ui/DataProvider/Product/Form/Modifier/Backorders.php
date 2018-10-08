<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\CatalogInventory\Ui\DataProvider\Product\Form\Modifier\AdvancedInventory as AdvancedInventoryModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Remove "Backorders" related fields from "Advanced Inventory" modal panel.
 */
class Backorders extends AbstractModifier
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
     * @param ArrayManager $arrayManager
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     */
    public function __construct(
        ArrayManager $arrayManager,
        IsSingleSourceModeInterface $isSingleSourceMode
    ) {
        $this->arrayManager = $arrayManager;
        $this->isSingleSourceMode = $isSingleSourceMode;
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
        $stockDataPath = $this->arrayManager->findPath(
            AdvancedInventoryModifier::STOCK_DATA_FIELDS,
            $meta,
            null,
            'children'
        );

        if (null === $stockDataPath || $this->isSingleSourceMode->execute()) {
            return $meta;
        }

        $backordersPath = $stockDataPath . '/children/container_backorders/arguments/data/config';
        $meta = $this->arrayManager->set(
            $backordersPath,
            $meta,
            [
                'visible' => 0,
                'imports' => '',
            ]
        );

        return $meta;
    }
}
