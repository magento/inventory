<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShippingAdminUi\Model;

use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterface;

class SortSourcesAfterSourceSelectionAlgorithm
{
    /**
     * Collect source codes from the selection result
     *
     * @param SourceSelectionResultInterface $sourceSelectionResult
     *
     * @return array sorted source codes
     */
    public function execute(SourceSelectionResultInterface $sourceSelectionResult): array
    {
        $sources = [];

        foreach ($sourceSelectionResult->getSourceSelectionItems() as $item) {
            $sourceCode = $item->getSourceCode();
            if (!in_array($sourceCode, $sources)) {
                $sources[] = $sourceCode;
            }
        }

        return $sources;
    }
}
