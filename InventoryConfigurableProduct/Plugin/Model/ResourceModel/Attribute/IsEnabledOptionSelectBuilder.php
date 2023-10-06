<?php
/************************************************************************
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\Model\ResourceModel\Attribute;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionSelectBuilderInterface;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;

/**
 * Plugin for OptionSelectBuilderInterface to add "enabled" filter.
 */
class IsEnabledOptionSelectBuilder
{
    /**
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        private readonly ProductAttributeRepositoryInterface $attributeRepository,
        private readonly MetadataPool $metadataPool
    ) {
    }

    /**
     * Add "enabled" filter to select.
     *
     * @param OptionSelectBuilderInterface $subject
     * @param Select $select
     * @param AbstractAttribute $superAttribute
     * @param int $productId
     * @param ScopeInterface $scope
     * @return Select
     *
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSelect(
        OptionSelectBuilderInterface $subject,
        Select $select,
        AbstractAttribute $superAttribute,
        int $productId,
        ScopeInterface $scope
    ) {
        $storeId = $scope->getId();
        $status = $this->attributeRepository->get(ProductInterface::STATUS);
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();

        $select->joinInner(
            ['entity_status_global' => $status->getBackendTable()],
            "entity_status_global.{$linkField} = entity.{$linkField}"
            . " AND entity_status_global.attribute_id = {$status->getAttributeId()}"
            . " AND entity_status_global.store_id = " . Store::DEFAULT_STORE_ID,
            []
        )->joinLeft(
            ['entity_status_store' => $status->getBackendTable()],
            "entity_status_store.{$linkField} = entity.{$linkField}"
            . " AND entity_status_store.attribute_id = {$status->getAttributeId()}"
            . " AND entity_status_store.store_id = {$storeId}",
            []
        )->where(
            $select->getConnection()->getIfNullSql('entity_status_global.value', 'entity_status_store.value') . ' = ?',
            ProductStatus::STATUS_ENABLED
        );

        return $select;
    }
}
