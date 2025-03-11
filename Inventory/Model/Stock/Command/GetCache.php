<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\Stock\Command;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\InventoryApi\Api\Data\StockInterface;

/**
 * @inheritdoc
 */
class GetCache implements GetInterface, ResetAfterRequestInterface
{
    /**
     * @var Get
     */
    private $getStock;

    /**
     * @var StockInterface[]
     */
    private $stocks = [];

    /**
     * @param Get $getStock
     */
    public function __construct(
        Get $getStock
    ) {
        $this->getStock = $getStock;
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->stocks = [];
    }

    /**
     * @inheritdoc
     */
    public function execute(int $stockId): StockInterface
    {
        if (!isset($this->stocks[$stockId])) {
            $this->stocks[$stockId] = $this->getStock->execute($stockId);
        }
        return $this->stocks[$stockId];
    }
}
