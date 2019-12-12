<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area;

class HandleSearchTerm
{
    /**
     * @var array
     */
    private $handlers;

    /**
     * @param array $handlers
     */
    public function __construct(array $handlers = [])
    {
        $this->handlers = $handlers;
    }

    /**
     * @param string $searchTerm
     * @return array
     */
    public function execute(string $searchTerm) : array
    {
        $result = [];
        foreach ($this->handlers as $handlerName => $handler) {
            $result[$handlerName] = $handler->execute($searchTerm);
        }

        return $result;
    }
}
