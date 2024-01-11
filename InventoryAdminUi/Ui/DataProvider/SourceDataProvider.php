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
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Ui\DataProvider\SearchResultFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

/**
 * Data provider for admin source grid.
 *
 * @api
 */
class SourceDataProvider extends DataProvider
{
    /**
     * constant for Source Form Name
     *
     * @const SOURCE_FORM_NAME
     */
    public const SOURCE_FORM_NAME = 'inventory_source_form_data_source';

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
     * @var PoolInterface
     */
    private $pool;

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
     * @param PoolInterface|null $pool
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
        PoolInterface $pool = null
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
        $this->pool = $pool ?: ObjectManager::getInstance()->get(PoolInterface::class);
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

        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $data = $modifier->modifyData($data);
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getSearchResult()
    {
        $searchCriteria = $this->getSearchCriteria();
        $result = $this->sourceRepository->getList($searchCriteria);

        return $this->searchResultFactory->create(
            $result->getItems(),
            $result->getTotalCount(),
            $searchCriteria,
            SourceInterface::SOURCE_CODE
        );
    }

    /**
     * Get total sources count, with or without filter be source name.
     *
     * Get total sources count, with or without filter in order to ui/grid/columns/multiselect::updateState()
     * works correctly with sources selection.
     *
     * @return int
     */
    private function getSourcesCount(): int
    {
        if (!$this->sourceCount) {
            $this->sourceCount = $this->getSearchResult()->getTotalCount();
        }

        return $this->sourceCount;
    }
}
