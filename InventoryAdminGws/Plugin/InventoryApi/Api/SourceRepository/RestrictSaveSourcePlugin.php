<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminGws\Plugin\InventoryApi\Api\SourceRepository;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\InventoryAdminGws\Model\IsSourceAllowedForCurrentUser;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * Restrict source by websites for current user.
 */
class RestrictSaveSourcePlugin
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
     * Verify, if source allowed to be saved for current user.
     *
     * @param SourceRepositoryInterface $subject
     * @param SourceInterface $source
     * @return void
     * @throws CouldNotSaveException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        SourceRepositoryInterface $subject,
        SourceInterface $source
    ): void {
        if (!$this->isSourceAllowedForCurrentUser->execute($source->getSourceCode())) {
            throw new CouldNotSaveException(__('Not enough permissions to operate with inventory.'));
        }
    }
}
