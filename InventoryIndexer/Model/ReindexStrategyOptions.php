<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryIndexer\Model;

/**
 * Configuration options for reindex strategy.
 */
class ReindexStrategyOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'sync',
                'label' => __('Synchronous'),
            ],
            [
                'value' => 'async',
                'label' => __('Asynchronous'),
            ],
        ];
    }
}
