<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Ui\DataProvider\Product\Listing\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Model\GetAllowedProductTypesForSourceItemManagementInterface;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Quantity Per Source modifier on CatalogInventory Product Grid
 */
class QuantityPerSource extends AbstractModifier
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var GetAllowedProductTypesForSourceItemManagementInterface
     */
    private $getAllowedProductTypesForSourceItemManagement;

    /**
     * @var array
     */
    private $sourcesBySourceCodes = [];

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param null $isSourceItemManagementAllowedForProductType @deprecated
     * @param SourceRepositoryInterface $sourceRepository
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param GetAllowedProductTypesForSourceItemManagementInterface $getAllowedProductTypesForSourceItemManagement
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        $isSourceItemManagementAllowedForProductType,
        SourceRepositoryInterface $sourceRepository,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        SearchCriteriaBuilder $searchCriteriaBuilder = null,
        SourceItemRepositoryInterface $sourceItemRepository = null,
        GetAllowedProductTypesForSourceItemManagementInterface $getAllowedProductTypesForSourceItemManagement = null
    ) {
        $objectManager = ObjectManager::getInstance();
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->sourceRepository = $sourceRepository;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder ?: $objectManager->get(SearchCriteriaBuilder::class);
        $this->sourceItemRepository = $sourceItemRepository ?:
            $objectManager->get(SourceItemRepositoryInterface::class);
        $this->getAllowedProductTypesForSourceItemManagement = $getAllowedProductTypesForSourceItemManagement ?:
            $objectManager->get(GetAllowedProductTypesForSourceItemManagementInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        if (0 === $data['totalRecords'] || true === $this->isSingleSourceMode->execute()) {
            return $data;
        }

        $data['items'] = $this->getSourceItemsData($data['items']);

        return $data;
    }

    /**
     * Add qty per source to the items.
     *
     * @param array $dataItems
     * @return array
     */
    private function getSourceItemsData(array $dataItems): array
    {
        $allowedProductTypes = $this->getAllowedProductTypesForSourceItemManagement->execute();

        foreach ($dataItems as $key => $item) {
            if (in_array($item['type_id'], $allowedProductTypes)) {
                $sku = htmlspecialchars_decode($item['sku'], ENT_QUOTES | ENT_SUBSTITUTE);
                $dataItems[$key]['quantity_per_source'] = $this->getQuantityPerSourceItemData($sku);
            }
        }

        unset($item);

        return $dataItems;
    }

    /**
     * Get quantity per source data for product.
     *
     * @param string $sku
     * @return array
     */
    private function getQuantityPerSourceItemData(string $sku): array
    {
        $sourceItems = $this->getSourceItemsBySku->execute($sku);
        $sourcesBySourceCode = $this->getSourcesBySourceItems($sourceItems);

        $itemData = [];
        foreach ($sourceItems as $sourceItem) {
            $source = $sourcesBySourceCode[$sourceItem->getSourceCode()];
            $itemData[] = [
                'source_name' => $source->getName(),
                'source_code' => $sourceItem->getSourceCode(),
                'qty' => (float) $sourceItem->getQuantity(),
            ];
        }

        return $itemData;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        if (true === $this->isSingleSourceMode->execute()) {
            return $meta;
        }

        $meta = array_replace_recursive(
            $meta,
            [
                'product_columns' => [
                    'children' => [
                        'quantity_per_source' => $this->getQuantityPerSourceMeta(),
                        'qty' => [
                            'arguments' => [
                                'data' => [
                                    'disabled' => true
                                ]
                            ],
                        ],
                    ],
                ],
            ]
        );
        return $meta;
    }

    /**
     * Qty per source metadata for rendering.
     *
     * @return array
     */
    private function getQuantityPerSourceMeta(): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'sortOrder' => 76,
                        'filter' => false,
                        'sortable' => false,
                        'label' => __('Quantity per Source'),
                        'dataType' => Text::NAME,
                        'componentType' => Column::NAME,
                        'component' => 'Magento_InventoryCatalogAdminUi/js/product/grid/cell/quantity-per-source',
                    ]
                ],
            ],
        ];
    }

    /**
     * Get all sources by source items codes.
     *
     * @param SourceItemInterface[] $sourceItems
     * @return array
     */
    private function getSourcesBySourceItems(array $sourceItems): array
    {
        $newSourceCodes = $sourcesBySourceCodes = [];

        foreach ($sourceItems as $sourceItem) {
            $sourceCode = $sourceItem->getSourceCode();
            if (isset($this->sourcesBySourceCodes[$sourceCode])) {
                $sourcesBySourceCodes[$sourceCode] = $this->sourcesBySourceCodes[$sourceCode];
            } else {
                $newSourceCodes[] = $sourceCode;
            }
        }

        if ($newSourceCodes) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(SourceInterface::SOURCE_CODE, $newSourceCodes, 'in')
                ->create();
            $newSources = $this->sourceRepository->getList($searchCriteria)->getItems();

            foreach ($newSources as $source) {
                $this->sourcesBySourceCodes[$source->getSourceCode()] = $source;
                $sourcesBySourceCodes[$source->getSourceCode()] = $source;
            }
        }

        return $sourcesBySourceCodes;
    }
}
