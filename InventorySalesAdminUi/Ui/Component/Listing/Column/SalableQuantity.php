<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesAdminUi\Model\ResourceModel\GetAssignedStockIdsBySku;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Add grid column with salable quantity data
 */
class SalableQuantity extends Column
{
    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var GetSalableQuantityDataBySku
     */
    private $getSalableQuantityDataBySku;

    /**
     * @var GetAssignedStockIdsBySku
     */
    private $getAssignedStockIdsBySku;

    /**
     * @var int
     */
    private $maximumStocksToShow;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param GetSalableQuantityDataBySku $getSalableQuantityDataBySku
     * @param GetAssignedStockIdsBySku $getAssignedStockIdsBySku
     * @param int $maximumStocksToShow
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        IsSingleSourceModeInterface $isSingleSourceMode,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        GetAssignedStockIdsBySku $getAssignedStockIdsBySku,
        int $maximumStocksToShow,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->getAssignedStockIdsBySku = $getAssignedStockIdsBySku;
        $this->maximumStocksToShow = $maximumStocksToShow;
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if ($dataSource['data']['totalRecords'] > 0) {
            foreach ($dataSource['data']['items'] as &$row) {
                $row['salable_quantity'] =
                    $this->isSourceItemManagementAllowedForProductType->execute($row['type_id']) === true
                    ? $this->getSalableQuantityItemData($row['sku'])
                    : [];
            }
        }
        unset($row);

        return $dataSource;
    }

    /**
     * Get salable quantity data for product
     *
     * @param string $sku
     * @return array
     */
    private function getSalableQuantityItemData(string $sku): array
    {
        $sku = htmlspecialchars_decode($sku, ENT_QUOTES | ENT_SUBSTITUTE);

        $stockIds = $this->getAssignedStockIdsBySku->execute($sku);
        if (count($stockIds) > $this->maximumStocksToShow) {
            return [
                [
                    'manage_stock' => true,
                    'message' => __('Associated to %1 stocks', count($stockIds)),
                ]
            ];
        }

        $salableQuantityData = $this->getSalableQuantityDataBySku->execute($sku);

        return $salableQuantityData;
    }
}
