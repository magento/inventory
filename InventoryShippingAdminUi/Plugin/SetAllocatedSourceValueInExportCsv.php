<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryShippingAdminUi\Plugin;

use Magento\InventoryShippingAdminUi\Model\ResourceModel\GetAllocatedSourcesForOrder;

/**
 * Set the value of Allocated Source column in sales order export csv Plugin Class
 */
class SetAllocatedSourceValueInExportCsv
{
    /**
     * Allocate Sources Attribute code
     *
     * @var string
     */
    private static $allocateSourcesAttributeCode = 'allocated_sources';

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
     */
    public function __construct(
        GetAllocatedSourcesForOrder $getAllocatedSourcesForOrder
    ) {
        $this->getAllocatedSourcesForOrder = $getAllocatedSourcesForOrder;
    }

    /**
     * Set the allocated source value to the allocated source column in export csv.
     *
     * @param $subject
     * @param $result
     * @param $document
     * @param $fields
     * @param $options
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetRowData($subject, $result, $document, $fields, $options)
    {
        $i = 0;
        foreach ($fields as $column) {
            if ($column === self::$allocateSourcesAttributeCode) {
                $allocated_sources = $this->getAllocatedSourcesForOrder->execute((int)$document['entity_id']);
                if(count($allocated_sources) > 0)
                    $result[$i] = $allocated_sources[0];
            }
            $i++;
            continue;
        }
        return $result;
    }
}
