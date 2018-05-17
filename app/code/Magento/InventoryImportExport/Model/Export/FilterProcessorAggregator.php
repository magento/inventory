<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Export;

use Magento\Framework\Exception\LocalizedException;
use Magento\Inventory\Model\ResourceModel\SourceItem\Collection;

/**
 * @api
 */
class FilterProcessorAggregator
{
    /**
     * @var FilterProcessorInterface[]
     */
    private $handler;

    /**
     * @param FilterProcessorInterface[] $handler
     * @throws LocalizedException
     */
    public function __construct(array $handler = [])
    {
        foreach ($handler as $filterProcessor) {
            if (!($filterProcessor instanceof FilterProcessorInterface)) {
                throw new LocalizedException(__(
                    'Filter handler must be instance of "%interface"',
                    ['interface' => FilterProcessorInterface::class]
                ));
            }
        }

        $this->handler = $handler;
    }

    /**
     * @param string $type
     * @param Collection $collection
     * @param string $columnName
     * @param string|array $value
     * @return void
     * @throws LocalizedException
     */
    public function process($type, Collection $collection, string $columnName, $value): void
    {
        if (!isset($this->handler[$type])) {
            throw new LocalizedException(__(
                'No filter processor for "%type" given.',
                ['type' => $type]
            ));
        }
        $this->handler[$type]->process($collection, $columnName, $value);
    }
}
