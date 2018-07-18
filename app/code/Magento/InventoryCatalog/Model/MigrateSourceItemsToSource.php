<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

class MigrateSourceItemsToSource implements MigrateSourceItemsToSourceInterface
{
    /**
     * @var SourceItemsDeleteInterface
     */
    private $sourceItemsDelete;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    public function __construct(
        SourceItemsDeleteInterface $sourceItemsDelete,
        SourceItemsSaveInterface $sourceItemsSave
    ) {
        $this->sourceItemsDelete = $sourceItemsDelete;
        $this->sourceItemsSave = $sourceItemsSave;
    }

    /**
     * @param string $migrationSourceCode
     * @param SourceItem[] $sourceItems
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function execute(string $migrationSourceCode, array $sourceItems)
    {
        $this->sourceItemsDelete->execute($sourceItems);

        foreach ($sourceItems as $sourceItem) {
            $sourceItem->setSourceCode($migrationSourceCode);
        }

        $this->sourceItemsSave->execute($sourceItems);
    }
}
