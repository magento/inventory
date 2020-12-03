<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductFrontendUi\Plugin\ConfigurableProduct\Block\Product\View\Type;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as Subject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class for adding info about sales channel and skus.
 */
class AddAdditionalInfo
{
    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Json $jsonSerializer
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Json $jsonSerializer,
        StoreManagerInterface $storeManager
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->storeManager = $storeManager;
    }

    /**
     * Add data about sales channel info and sku.
     *
     * @param Subject $configurable
     * @param string $result
     * @return string
     */
    public function afterGetJsonConfig(Subject $configurable, string $result): string
    {
        $jsonConfig = $this->jsonSerializer->unserialize($result);

        $jsonConfig['channel'] = SalesChannelInterface::TYPE_WEBSITE;
        $jsonConfig['salesChannelCode'] = $this->storeManager->getWebsite()->getCode();
        $jsonConfig['sku'] = $this->getProductVariationsSku($configurable);

        return $this->jsonSerializer->serialize($jsonConfig);
    }

    /**
     * Get product variations sku.
     *
     * @param Subject $configurable
     * @return array
     */
    private function getProductVariationsSku(Subject $configurable): array
    {
        $skus = [];
        foreach ($configurable->getAllowProducts() as $product) {
            $skus[$product->getId()] = $product->getSku();
        }

        return $skus;
    }
}
