<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\Source\Command;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;

/**
 * @inheritdoc
 */
class GetSourcesAssignedToStockOrderedByPriorityCache implements
    GetSourcesAssignedToStockOrderedByPriorityInterface,
    ResetAfterRequestInterface
{
    /**
     * @var GetSourcesAssignedToStockOrderedByPriority
     */
    private $getSourcesAssignedToStock;

    /**
     * @var array
     */
    private $sourcesAssignedToStock = [];

    /**
     * @param GetSourcesAssignedToStockOrderedByPriority $getSourcesAssignedToStockOrderedByPriority
     */
    public function __construct(
        GetSourcesAssignedToStockOrderedByPriority $getSourcesAssignedToStockOrderedByPriority
    ) {
        $this->getSourcesAssignedToStock = $getSourcesAssignedToStockOrderedByPriority;
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->sourcesAssignedToStock = [];
    }

    /**
     * @inheritdoc
     */
    public function execute(int $stockId): array
    {
        if (!isset($this->sourcesAssignedToStock[$stockId])) {
            $this->sourcesAssignedToStock[$stockId] = $this->getSourcesAssignedToStock->execute($stockId);
        }
        return $this->sourcesAssignedToStock[$stockId];
    }
}
