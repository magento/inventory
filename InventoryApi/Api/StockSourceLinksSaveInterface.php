<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Api;

/**
 * Service method for stock source links save multiple
 * Performance efficient API
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface StockSourceLinksSaveInterface
{
    /**
     * Save StockSourceLink list data
     *
     * @param \Magento\InventoryApi\Api\Data\StockSourceLinkInterface[] $links
     * @return void
     * @throws \Magento\Framework\Validation\ValidationException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function execute(array $links): void;
}
