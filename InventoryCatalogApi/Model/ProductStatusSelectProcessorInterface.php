<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Process select and add filter by product status for given stock.
 *
 * @api
 */
interface ProductStatusSelectProcessorInterface
{
    /**
     * Filter select by product status for given stock.
     *
     * @param \Magento\Framework\DB\Select $select
     * @param int $stockId
     * @return \Magento\Framework\DB\Select
     * @throws NoSuchEntityException
     */
    public function execute(Select $select, int $stockId): Select;
}
