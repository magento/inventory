<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Model\ReturnProcessor\Result;

use Magento\InventorySalesApi\Model\ReturnProcessor\Result\SourceDeductedOrderItem;

/**
 * DTO used as returned type of GetSourceDeductedOrderItemsInterface
 */
class SourceDeductedOrderItemsResult
{
    /**
     * @var string
     */
    private $sourceCode;

    /**
     * @var SourceDeductedOrderItem[]
     */
    private $items;

    /**
     * @param string $sourceCode
     * @param array $items
     */
    public function __construct(string $sourceCode, array $items)
    {
        $this->sourceCode = $sourceCode;
        $this->items = $items;
    }

    /**
     * Returns the source code associated with the current source deduction result.
     *
     * @return string
     */
    public function getSourceCode(): string
    {
        return $this->sourceCode;
    }

    /**
     * Returns an array of SourceDeductedOrderItem objects associated with the current source deduction result.
     *
     * @return SourceDeductedOrderItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
