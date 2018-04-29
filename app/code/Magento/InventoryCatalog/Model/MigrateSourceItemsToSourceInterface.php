<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

interface MigrateSourceItemsToSourceInterface
{
    /**
     * @param string $migrationSourceCode
     * @param SourceItem[] $sourceItems
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function execute(string $migrationSourceCode, array $sourceItems);
}
