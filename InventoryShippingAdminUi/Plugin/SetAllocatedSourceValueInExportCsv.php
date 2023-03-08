<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryShippingAdminUi\Plugin;

use Magento\InventoryShippingAdminUi\Model\ResourceModel\GetAllocatedSourcesForOrder;
use Magento\Ui\Model\Export\MetadataProvider;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\View\Element\UiComponentInterface;

/**
 * Set the value of Allocated Source column in sales order export csv Plugin Class
 */
class SetAllocatedSourceValueInExportCsv
{
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var string
     */
    private static $allocateSourcesAttributeCode = 'allocated_sources';

    /**
     * @var string
     */
    private static $salesOrderComponentName = 'sales_order_grid';

    /**
     * Allocated Sources for order resources
     *
     * @var GetAllocatedSourcesForOrder
     */
    private $getAllocatedSourcesForOrder;

    /**
     * Construct Method for SetAllocatedSourceValueInExportCsv Plugin
     *
     * @param GetAllocatedSourcesForOrder $getAllocatedSourcesForOrder
     * @param Filter $filter
     */
    public function __construct(
        GetAllocatedSourcesForOrder $getAllocatedSourcesForOrder,
        Filter $filter
    ) {
        $this->getAllocatedSourcesForOrder = $getAllocatedSourcesForOrder;
        $this->filter = $filter;
    }

    /**
     * Set the allocated source value to the allocated source column in export csv.
     *
     * @param MetadataProvider $subject
     * @param array $result
     * @param object $document
     * @param array $fields
     * @param array $options
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetRowData(MetadataProvider $subject, $result, $document, $fields, $options)
    {
        $component = $this->filter->getComponent();
        if (!$component instanceof UiComponentInterface) {
            return $result;
        }
        if ($component->getName() === self::$salesOrderComponentName) {
            foreach ($fields as $key => $column) {
                if ($column === self::$allocateSourcesAttributeCode) {
                    $allocated_sources = $this->getAllocatedSourcesForOrder->execute((int)$document['entity_id']);
                    if (count($allocated_sources) > 0) {
                        $result[$key] = implode(",", $allocated_sources);
                    }
                }
            }
        }
        return $result;
    }
}
