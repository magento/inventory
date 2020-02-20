<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminGws\Plugin\InventoryApi\Api\SourceRepository;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryAdminGws\Model\IsSourceAllowedForCurrentUser;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * Restrict source by websites for current user.
 */
class RestrictGetSourcePlugin
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
     * Verify, if source allowed for current user.
     *
     * @param SourceRepositoryInterface $subject
     * @param string $sourceCode
     * @return void
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeGet(
        SourceRepositoryInterface $subject,
        string $sourceCode
    ): void {
        if (!$this->isSourceAllowedForCurrentUser->execute($sourceCode)) {
            throw new NoSuchEntityException();
        }
    }
}
