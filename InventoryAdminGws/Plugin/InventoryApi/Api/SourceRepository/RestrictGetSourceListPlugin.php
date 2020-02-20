<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminGws\Plugin\InventoryApi\Api\SourceRepository;

use Magento\InventoryAdminGws\Model\IsSourceAllowedForCurrentUser;
use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * Restrict source by websites for current user.
 */
class RestrictGetSourceListPlugin
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
     * Filter restricted sources for current user.
     *
     * @param SourceRepositoryInterface $subject
     * @param SourceSearchResultsInterface $result
     * @return SourceSearchResultsInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        SourceRepositoryInterface $subject,
        SourceSearchResultsInterface $result
    ): SourceSearchResultsInterface {
        $allowedSources = [];
        foreach ($result->getItems() as $source) {
            if ($this->isSourceAllowedForCurrentUser->execute($source->getSourceCode())) {
                $allowedSources[] = $source;
            }
        }
        $result->setItems($allowedSources);

        return $result;
    }
}
