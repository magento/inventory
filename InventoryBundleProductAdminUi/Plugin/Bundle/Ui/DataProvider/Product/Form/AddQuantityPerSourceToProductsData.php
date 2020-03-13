<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductAdminUi\Plugin\Bundle\Ui\DataProvider\Product\Form;

use Magento\Bundle\Ui\DataProvider\Product\BundleDataProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogAdminUi\Model\GetQuantityInformationPerSourceBySkus;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * On multi source mode add data "Quantity Per Source" to loaded items for modal window.
 */
class AddQuantityPerSourceToProductsData
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var GetQuantityInformationPerSourceBySkus
     */
    private $getQuantityInformationPerSourceBySkus;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param GetQuantityInformationPerSourceBySkus $getQuantityInformationPerSourceBySkus
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        GetQuantityInformationPerSourceBySkus $getQuantityInformationPerSourceBySkus
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->getQuantityInformationPerSourceBySkus = $getQuantityInformationPerSourceBySkus;
    }

    /**
     * Add data "Quantity Per Source" to items on modal window for multi source mode.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param BundleDataProvider $subject
     * @param array $result
     * @return array
     * @throws NoSuchEntityException
     */
    public function afterGetData(BundleDataProvider $subject, array $result): array
    {
        if ($this->isSingleSourceMode->execute()) {
            return $result;
        }

        $skus = array_column($result['items'], 'sku');
        $sourceItemsData = $this->getQuantityInformationPerSourceBySkus->execute($skus);
        foreach ($result['items'] as &$productLinkData) {
            $productLinkData['quantity_per_source'] = $sourceItemsData[$productLinkData['sku']] ?? [];
        }

        return $result;
    }
}
