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
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Add stock list for stock list dropdown in "Advanced Inventory" panel.
 */
class AddStockList extends AbstractModifier
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param StockRepositoryInterface $stockRepository
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        StockRepositoryInterface $stockRepository,
        ArrayManager $arrayManager
    ) {
        $this->stockRepository = $stockRepository;
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

        $stockListPath = $stockDataPath . '/children/stock_list';
        $meta = $this->arrayManager->set(
            $stockListPath,
            $meta,
            [
                'attributes' => [
                    'class' => \Magento\Ui\Component\Form\Field::class,
                    'name' => 'stock_list',
                    'formElement' => 'select',
                    'component' => 'Magento_InventoryConfigurationAdminUi/js/product/form/stock-list',
                    'sortOrder' => '10',
                ],
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'select',
                            'componentType' => \Magento\Ui\Component\Form\Field::NAME,
                            'label' => 'Stock List',
                            'dataType' => 'text',
                            'component' => 'Magento_InventoryConfigurationAdminUi/js/product/form/stock-list',
                            'sortOrder' => '10',
                            'listens' => [
                                '${$.namespace}.${$.namespace}.sources.assigned_sources:recordData' =>
                                    'onAssignSourcesChanged',
                            ],
                            'dataScope' => 'stock_list',
                            'list' => $this->getStockListData(),

                        ],
                    ],
                ],
            ]
        );


        return $meta;
    }

    /**
     * @return array
     */
    private function getStockListData(): array
    {
        $stocks = $this->stockRepository->getList();
        $data = [];
        foreach ($stocks->getItems() as $stock) {
            $data[] = [
                'value' => (int)$stock->getStockId(),
                'label' => (string)$stock->getName(),
            ];
        }

        return $data;
    }
}
