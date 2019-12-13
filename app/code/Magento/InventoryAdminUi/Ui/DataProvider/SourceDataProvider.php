<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Ui\DataProvider;

use Magento\Backend\Model\Session;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Store\Model\ResourceModel\Website\Collection;
use Magento\Ui\DataProvider\SearchResultFactory;

/**
 * Data provider for admin source grid.
 *
 * @api
 */
class SourceDataProvider extends DataProvider
{
    const SOURCE_FORM_NAME = 'inventory_source_form_data_source';

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var Session
     */
    private $session;

    /**
     * Total source count.
     *
     * @var int
     */
    private $sourceCount;

    /**
     * @var Collection
     */
    private $websiteCollection;

    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param SourceRepositoryInterface $sourceRepository
     * @param SearchResultFactory $searchResultFactory
     * @param Session $session
     * @param array $meta
     * @param array $data
     * @param Collection|null $websiteCollection
     * @param GetStockSourceLinksInterface|null $getStockSourceLinks
     * @param StockRepositoryInterface|null $stockRepository
     * @SuppressWarnings(PHPMD.ExcessiveParameterList) All parameters are needed for backward compatibility
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        SourceRepositoryInterface $sourceRepository,
        SearchResultFactory $searchResultFactory,
        Session $session,
        array $meta = [],
        array $data = [],
        Collection $websiteCollection = null,
        GetStockSourceLinksInterface $getStockSourceLinks = null,
        StockRepositoryInterface $stockRepository = null
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->sourceRepository = $sourceRepository;
        $this->searchResultFactory = $searchResultFactory;
        $this->session = $session;
        $this->websiteCollection = $websiteCollection ?: ObjectManager::getInstance()->get(Collection::class);
        $this->getStockSourceLinks = $getStockSourceLinks ?: ObjectManager::getInstance()
            ->get(GetStockSourceLinksInterface::class);
        $this->stockRepository = $stockRepository ?: ObjectManager::getInstance()->get(StockRepositoryInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data = parent::getData();
        if (self::SOURCE_FORM_NAME === $this->name) {
            // It is need for support of several fieldsets.
            // For details see \Magento\Ui\Component\Form::getDataSourceData
            if ($data['totalRecords'] > 0) {
                $sourceCode = $data['items'][0][SourceInterface::SOURCE_CODE];
                $sourceGeneralData = $data['items'][0];
                $sourceGeneralData['disable_source_code'] = !empty($sourceGeneralData['source_code']);
                $dataForSingle[$sourceCode] = [
                    'general' => $sourceGeneralData,
                ];
                return $dataForSingle;
            }
            $sessionData = $this->session->getSourceFormData(true);
            if (null !== $sessionData) {
                // For details see \Magento\Ui\Component\Form::getDataSourceData
                $data = [
                    '' => $sessionData,
                ];
            }
        }
        $data['totalRecords'] = $this->getSourcesCount();
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getSearchResult()
    {
        $searchCriteria = $this->getSearchCriteria();
        $result = $this->sourceRepository->getList($searchCriteria);
        $codes = [];
        $websites = $this->websiteCollection->getItems();
        foreach ($websites as $website) {
            $codes[] = $website->getCode();
        }
        $items = [];
        foreach ($result->getItems() as $item) {
            if ($this->isSourceItemAllowed($item->getSourceCode(), $codes)) {
                $items[] = $item;
            }
        }

        $searchResult = $this->searchResultFactory->create(
            $items,
            count($items),
            $searchCriteria,
            SourceInterface::SOURCE_CODE
        );
        return $searchResult;
    }

    /**
     * Get total sources count, without filter be source name.
     *
     * Get total sources count, without filter in order to ui/grid/columns/multiselect::updateState()
     * works correctly with sources selection.
     *
     * @return int
     */
    private function getSourcesCount(): int
    {
        if (!$this->sourceCount) {
            $this->sourceCount = $this->sourceRepository->getList()->getTotalCount();
        }

        return $this->sourceCount;
    }

    /**
     * Verify source items is not restricted to display for admin user.
     *
     * @param string $sourceCode
     * @param array $codes
     * @return bool
     * @throws NoSuchEntityException
     */
    private function isSourceItemAllowed(string $sourceCode, array $codes): bool
    {
        $result = false;
        $this->filterBuilder->setField(StockSourceLinkInterface::SOURCE_CODE);
        $this->filterBuilder->setValue($sourceCode);
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter($this->filterBuilder->create())
            ->create();
        $salesChannels = [];
        foreach ($this->getStockSourceLinks->execute($searchCriteria)->getItems() as $link) {
            if (!isset($salesChannels[$link->getStockId()])) {
                $stock = $this->stockRepository->get((int)$link->getStockId());
                $salesChannels[$stock->getStockId()] = $stock->getExtensionAttributes()->getSalesChannels();
            }
        }
        foreach ($salesChannels as $stockSalesChannels) {
            foreach ($stockSalesChannels as $salesChannel) {
                if ($salesChannel[SalesChannelInterface::TYPE] !== SalesChannelInterface::TYPE_WEBSITE
                    || ($salesChannel[SalesChannelInterface::TYPE] === SalesChannelInterface::TYPE_WEBSITE
                        && in_array($salesChannel[SalesChannelInterface::CODE], $codes))) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }
}
