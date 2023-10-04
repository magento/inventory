<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\Model\ResourceModel\Attribute;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionSelectBuilderInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;

/**
 * Plugin for OptionSelectBuilderInterface to add "enabled" filter.
 */
class IsEnabledOptionSelectBuilder
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ProductAttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductAttributeRepositoryInterface $attributeRepository,
    ) {
        $this->storeManager = $storeManager;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Add "enabled" filter to select.
     *
     * @param OptionSelectBuilderInterface $subject
     * @param Select $select
     * @return Select
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSelect(
        OptionSelectBuilderInterface $subject,
        Select $select
    ) {
        $storeId = $this->storeManager->getStore()->getId();
        $statusAttributeId = $this->attributeRepository->get('status')->getAttributeId();

        $select->where(
            '((IF(EXISTS
                    (SELECT value
                        FROM `catalog_product_entity_int` AS `product_entity_int`
                        WHERE product_entity_int.store_id = ' . $storeId . '
                            AND product_entity_int.attribute_id = ' . $statusAttributeId . '
                            AND product_entity_int.row_id = entity.row_id),
                    (SELECT `value`
                        FROM `catalog_product_entity_int` AS `product_entity_int`
                        WHERE product_entity_int.store_id = ' . $storeId . '
                            AND product_entity_int.attribute_id = ' . $statusAttributeId . '
                            AND product_entity_int.row_id = entity.row_id),
                    (SELECT `value`
                        FROM `catalog_product_entity_int` AS `product_entity_int`
                        WHERE product_entity_int.store_id = ' . Store::DEFAULT_STORE_ID . '
                            AND product_entity_int.attribute_id = ' . $statusAttributeId . '
                            AND product_entity_int.row_id = entity.row_id)))
                    = ' . ProductStatus::STATUS_ENABLED . ')'
        );

        return $select;
    }
}
