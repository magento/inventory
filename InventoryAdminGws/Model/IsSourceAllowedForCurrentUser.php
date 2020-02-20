<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminGws\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;

/**
 * Verify source allowed for current user service.
 */
class IsSourceAllowedForCurrentUser
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var array
     */
    private $sourceCodes = [];

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param StockRepositoryInterface $stockRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetStockSourceLinksInterface $getStockSourceLinks,
        StockRepositoryInterface $stockRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->stockRepository = $stockRepository;
    }

    /**
     * Verify source allowed for current user.
     *
     * @param string $sourceCode
     * @return bool
     */
    public function execute(string $sourceCode): bool
    {
        if (isset($this->sourceCodes[$sourceCode])) {
            return $this->sourceCodes[$sourceCode];
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(StockSourceLinkInterface::SOURCE_CODE, $sourceCode)
            ->create();
        foreach ($this->getStockSourceLinks->execute($searchCriteria)->getItems() as $link) {
            if (!isset($this->salesChannels[$link->getStockId()])) {
                try {
                    $this->stockRepository->get((int)$link->getStockId());
                } catch (NoSuchEntityException $e) {
                    $this->sourceCodes[$sourceCode] = false;
                    return false;
                }
            }
        }
        $this->sourceCodes[$sourceCode] = true;

        return true;
    }
}
