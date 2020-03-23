<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductAdminUi\Plugin\Bundle\Ui\DataProvider\Product\Form;

use Magento\Bundle\Ui\DataProvider\Product\BundleDataProvider;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * In multi source mode add column "Quantity Per Source".
 */
class AddColumnQuantityPerSource
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
    }

    /**
     * Add column "Quantity Per Source" to modal window for multi source mode.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param BundleDataProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetMeta(BundleDataProvider $subject, array $result): array
    {
        if (!$this->isSingleSourceMode->execute()) {
            $result = array_replace_recursive($result, [
                'product_columns' => [
                    'children' => [
                        'quantity_per_source' => $this->getQuantityPerSourceMeta(),
                        'qty' => [
                            'arguments' => null,
                        ],
                    ],
                ],
            ]);
        }

        return $result;
    }

    /**
     * Config for column "Quantity Per Source".
     *
     * @return array
     */
    private function getQuantityPerSourceMeta(): array
    {
        $jsComponent = 'Magento_InventoryBundleProductAdminUi/js/form/element/grid-column-quantity-per-source';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'filter' => false,
                        'sortable' => false,
                        'label' => __('Quantity per Source'),
                        'dataType' => Text::NAME,
                        'componentType' => Column::NAME,
                        'component' => $jsComponent,
                    ]
                ],
            ],
        ];
    }
}
