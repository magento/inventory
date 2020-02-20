<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminGws\Plugin\InventoryApi\Api\SourceItemsSave;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\InventoryAdminGws\Model\IsSourceAllowedForCurrentUser;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

/**
 * Restrict source items by websites for current user.
 */
class RestrictSaveSourceItemsPlugin
{
    /**
     * @var IsSourceAllowedForCurrentUser
     */
    private $isSourceAllowedForCurrentUser;

    /**
     * @param IsSourceAllowedForCurrentUser $isSourceAllowedForCurrentUser
     */
    public function __construct(IsSourceAllowedForCurrentUser $isSourceAllowedForCurrentUser)
    {
        $this->isSourceAllowedForCurrentUser = $isSourceAllowedForCurrentUser;
    }

    /**
     * Verify, if source items allowed to be saved for current user.
     *
     * @param SourceItemsSaveInterface $subject
     * @param SourceItemInterface[] $sourceItems
     * @return array
     * @throws CouldNotSaveException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(
        SourceItemsSaveInterface $subject,
        array $sourceItems
    ): array {
        foreach ($sourceItems as $key => $sourceItem) {
            if (!$this->isSourceAllowedForCurrentUser->execute($sourceItem->getSourceCode())) {
                unset($sourceItems[$key]);
            }
        }
        if (!$sourceItems) {
            throw new CouldNotSaveException(__('Not enough permissions to operate with inventory.'));
        }

        return [$sourceItems];
    }
}
