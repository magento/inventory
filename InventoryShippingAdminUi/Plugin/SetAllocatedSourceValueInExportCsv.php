<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryShippingAdminUi\Plugin;

use Magento\InventoryShippingAdminUi\Model\ResourceModel\GetAllocatedSourcesForOrder;
use Magento\Ui\Model\Export\MetadataProvider;

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
        $i = 0;
        foreach ($fields as $column) {
            if ($column === self::$allocateSourcesAttributeCode) {
                $allocated_sources = $this->getAllocatedSourcesForOrder->execute((int)$document['entity_id']);
                if(count($allocated_sources) > 0)
                    $result[$i] = implode(", ", $allocated_sources);
            }
            $i++;
        }
        return $result;
    }
}
