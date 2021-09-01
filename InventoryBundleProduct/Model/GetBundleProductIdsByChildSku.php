<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model;

use Magento\Bundle\Model\Product\Type;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

/**
 * Retrieve bundle product IDs by child sku.
 */
class GetBundleProductIdsByChildSku
{
    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var Type
     */
    private $type;

    /**
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param Type $type
     */
    public function __construct(
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        Type $type
    ) {
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->type = $type;
    }

    /**
     * Return bundle product IDs by child sku.
     *
     * @param string $sku
     * @return array
     */
    public function execute(string $sku): array
    {
        try {
            $id = $this->getProductIdsBySkus->execute([$sku])[$sku];
        } catch (NoSuchEntityException $e) {
            return [];
        }
        return $this->type->getParentIdsByChild($id);
    }
}
