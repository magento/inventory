<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySkus;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Add grid column with salable quantity data
 */
class SalableQuantityBulk extends Column
{
    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var GetSalableQuantityDataBySkus
     */
    private $getSalableQuantityDataBySkus;

    /**
     * SalableQuantityBulk constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param GetSalableQuantityDataBySkus $getSalableQuantityDataBySkus
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        GetSalableQuantityDataBySkus $getSalableQuantityDataBySkus,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->getSalableQuantityDataBySkus = $getSalableQuantityDataBySkus;
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if ($dataSource['data']['totalRecords'] > 0) {
            $skus = [];
            foreach ($dataSource['data']['items'] as &$row) {
                if ($this->isSourceItemManagementAllowedForProductType->execute($row['type_id']) === true) {
                    $skus[] = $row['sku'];
                }
            }

            $salableQuantity = $this->getSalableQuantityDataBySkus->execute($skus);

            foreach ($dataSource['data']['items'] as &$row) {
                $row['salable_quantity'] = $salableQuantity[$row['sku']] ?? [];
            }
        }
        unset($row);
        unset($salableQuantity);

        return $dataSource;
    }
}
