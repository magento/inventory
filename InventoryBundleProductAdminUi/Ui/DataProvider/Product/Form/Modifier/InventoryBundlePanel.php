<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductAdminUi\Ui\DataProvider\Product\Form\Modifier;

use Magento\Bundle\Model\Product\Type;
use Magento\Bundle\Ui\DataProvider\Product\Form\Modifier\BundlePanel;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\InventoryCatalogAdminUi\Model\GetQuantityInformationPerSourceBySkus;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\Ui\Component\Form\Element\Input;

/**
 * Add column "Quantity Per Source" and sources data  to bundle product options grid.
 */
class InventoryBundlePanel extends AbstractModifier
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var GetQuantityInformationPerSourceBySkus
     */
    private $getQuantityInformationPerSourceBySkus;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param LocatorInterface $locator
     * @param GetQuantityInformationPerSourceBySkus $getQuantityInformationPerSourceBySkus
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        LocatorInterface $locator,
        GetQuantityInformationPerSourceBySkus $getQuantityInformationPerSourceBySkus,
        ArrayManager $arrayManager
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->locator = $locator;
        $this->getQuantityInformationPerSourceBySkus = $getQuantityInformationPerSourceBySkus;
        $this->arrayManager = $arrayManager;
    }

    /**
     * Add source data to linked to bundle product items only for multi source mode.
     *
     * @param array $data
     * @return array
     * @throws NoSuchEntityException
     */
    public function modifyData(array $data)
    {
        $product = $this->locator->getProduct();
        $modelId = (int)$product->getId();
        if ($product->getTypeId() === Type::TYPE_CODE
            && $modelId
            && isset($data[$modelId][BundlePanel::CODE_BUNDLE_OPTIONS][BundlePanel::CODE_BUNDLE_OPTIONS])
            && !$this->isSingleSourceMode->execute()
        ) {
            $data = $this->addQuantityPerSource($data, $modelId);
        }

        return $data;
    }

    /**
     * Add column "Quantity Per Source" to bundle selections.
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta): array
    {
        if ($this->locator->getProduct()->getTypeId() === Type::TYPE_CODE
            && !$this->isSingleSourceMode->execute()
        ) {
            $path = $this->arrayManager->findPath('bundle_selections', $meta, null);
            $meta = $this->arrayManager->merge(
                $path,
                $meta,
                [
                    'children' => [
                        'record' => [
                            'children' => [
                                'source_code' => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => $this->getQtyPerSourceConfig(),
                                        ],
                                    ],
                                ],
                            ],

                        ],
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'map' => [
                                    'quantity_per_source' => 'quantity_per_source',
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        return $meta;
    }

    /**
     * Config for field "Quantity Per Source" on dynamic rows.
     *
     * @return array
     */
    private function getQtyPerSourceConfig(): array
    {
        return [
            'componentType' => 'text',
            'component' => 'Magento_InventoryBundleProductAdminUi/js/form/element/quantity-per-source',
            'template' => 'ui/form/field',
            'dataScope' => 'quantity_per_source',
            'label' => __('Quantity Per Source'),
            'formElement' => Input::NAME,
            'sortOrder' => 95,
        ];
    }

    /**
     * Add quantity per source to data.
     *
     * @param array $data
     * @param int $modelId
     * @return array
     * @throws NoSuchEntityException
     */
    private function addQuantityPerSource(array $data, int $modelId): array
    {
        $selectionSkus = [];
        $bundleOptions = &$data[$modelId][BundlePanel::CODE_BUNDLE_OPTIONS][BundlePanel::CODE_BUNDLE_OPTIONS];
        foreach ($bundleOptions as $option) {
            if (isset($option['bundle_selections'])) {
                foreach ($option['bundle_selections'] as $selection) {
                    $selectionSkus[] = $selection['sku'];
                }
            }
        }
        $sourceItemsData = $this->getQuantityInformationPerSourceBySkus->execute($selectionSkus);
        foreach ($bundleOptions as &$option) {
            if (isset($option['bundle_selections'])) {
                foreach ($option['bundle_selections'] as &$selection) {
                    $selection['quantity_per_source'] = $sourceItemsData[$selection['sku']] ?? [];
                }
            }
        }

        return $data;
    }
}
