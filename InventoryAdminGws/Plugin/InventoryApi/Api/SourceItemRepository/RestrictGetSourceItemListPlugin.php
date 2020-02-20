<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminGws\Plugin\InventoryApi\Api\SourceItemRepository;

use Magento\InventoryAdminGws\Model\IsSourceAllowedForCurrentUser;
use Magento\InventoryApi\Api\Data\SourceItemSearchResultsInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;

/**
 * Restrict source item by websites for current user.
 */
class RestrictGetSourceItemListPlugin
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
     * Filter restricted source items for current user.
     *
     * @param SourceItemRepositoryInterface $subject
     * @param SourceItemSearchResultsInterface $result
     * @return SourceItemSearchResultsInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        SourceItemRepositoryInterface $subject,
        SourceItemSearchResultsInterface $result
    ): SourceItemSearchResultsInterface {
        $allowedSources = [];
        foreach ($result->getItems() as $sourceItem) {
            if ($this->isSourceAllowedForCurrentUser->execute($sourceItem->getSourceCode())) {
                $allowedSources[] = $sourceItem;
            }
        }
        $result->setItems($allowedSources);

        return $result;
    }
}
