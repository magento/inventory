<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\Validators;

use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

/**
 * Checks whether given value is an empty string
 */
class NotExistentSku
{
    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;


    public function __construct(
        GetProductIdsBySkusInterface $getProductIdsBySkus
    )
    {
        $this->getProductIdsBySkus = $getProductIdsBySkus;
    }

    /**
     * Checks whether given value is an empty string.
     *
     * @param string $fieldName
     * @param string $value
     * @return array
     */
    public function execute(string $fieldName, string $value): array
    {
        $errors = [];

        try {
            $this->getProductIdsBySkus->execute([$value]);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            $errors[] = __('Product with requested %field: %value was was not found.', ['field' => $fieldName, 'value' => $value]);
        }

        return $errors;
    }
}
