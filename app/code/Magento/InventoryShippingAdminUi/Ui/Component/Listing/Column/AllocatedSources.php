<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShippingAdminUi\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\InventoryShippingAdminUi\Model\ResourceModel\GetAllocatedSourcesForOrder;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Add order grid column for allocated sources. Prepare data
 */
class AllocatedSources extends Column
{
    /**
     * @var GetAllocatedSourcesForOrder
     */
    private $getAllocatedSourcesForOrder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param GetAllocatedSourcesForOrder $getAllocatedSourcesForOrder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        GetAllocatedSourcesForOrder $getAllocatedSourcesForOrder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->getAllocatedSourcesForOrder = $getAllocatedSourcesForOrder;
    }

    /**
     * Prepare data source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['totalRecords'])
            && $dataSource['data']['totalRecords'] > 0
        ) {
            foreach ($dataSource['data']['items'] as &$row) {
                $row['allocated_sources'] = $this->getAllocatedSourcesForOrder->execute((int)$row['entity_id']);
            }
        }
        unset($row);

        return $dataSource;
    }
}
