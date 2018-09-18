<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Inventory\Model\ResourceModel\SourceItem;

/**
 * Source item transaction manager
 */
class SourceItemTransactionManager
{
    /**
     * @var SourceItem
     */
    private $sourceItem;

    /**
     * @param SourceItem $sourceItem
     */
    public function __construct(SourceItem $sourceItem)
    {
        $this->sourceItem = $sourceItem;
    }

    /**
     * Begin transaction
     * @return void
     */
    public function begin(): void
    {
        $this->sourceItem->getConnection()->beginTransaction();
    }

    /**
     * Rollback transaction
     * @return void
     */
    public function rollback(): void
    {
        $this->sourceItem->getConnection()->rollBack();
    }

    /**
     * Commit transaction
     * @return void
     */
    public function commit(): void
    {
        $this->sourceItem->getConnection()->commit();
    }
}
