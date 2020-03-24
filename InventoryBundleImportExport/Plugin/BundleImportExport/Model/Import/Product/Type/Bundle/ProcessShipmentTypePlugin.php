<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleImportExport\Plugin\BundleImportExport\Model\Import\Product\Type\Bundle;

use Magento\BundleImportExport\Model\Import\Product\Type\Bundle;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Process shipment type for bundle products for multi stock mode.
 */
class ProcessShipmentTypePlugin
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     */
    public function __construct(IsSingleSourceModeInterface $isSingleSourceMode)
    {
        $this->isSingleSourceMode = $isSingleSourceMode;
    }

    /**
     * Convert shipment type to "Ship Separately" for bundle products in case multi source mode.
     *
     * @param Bundle $subject
     * @param array $rowData
     * @param bool $withDefaultValue
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforePrepareAttributesWithDefaultValueForSave(
        Bundle $subject,
        array $rowData,
        $withDefaultValue = true
    ): array {
        if (!$this->isSingleSourceMode->execute()) {
            $rowData['shipment_type'] = 'separately';
        }

        return [$rowData, $withDefaultValue];
    }
}
