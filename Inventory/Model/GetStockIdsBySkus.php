<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Model\GetSourceCodesBySkusInterface;
use Magento\InventoryApi\Model\GetStockIdsBySkusInterface;

class GetStockIdsBySkus implements GetStockIdsBySkusInterface
{
    /**
     * @var GetSourceCodesBySkusInterface
     */
    private $getSourceCodesBySkus;

    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param GetSourceCodesBySkusInterface $getSourceCodesBySkus
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        GetSourceCodesBySkusInterface $getSourceCodesBySkus,
        GetStockSourceLinksInterface $getStockSourceLinks,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->getSourceCodesBySkus = $getSourceCodesBySkus;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus): array
    {
        $sourceCodes = $this->getSourceCodesBySkus->execute($skus);
        $this->searchCriteriaBuilder->addFilter(StockSourceLinkInterface::SOURCE_CODE, $sourceCodes, 'in');
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $stockSourceLinks = $this->getStockSourceLinks->execute($searchCriteria)->getItems();
        $stockIds = [];
        foreach ($stockSourceLinks as $stockSourceLink) {
            $stockIds[] = $stockSourceLink->getStockId();
        }

        return array_unique($stockIds);
    }
}
