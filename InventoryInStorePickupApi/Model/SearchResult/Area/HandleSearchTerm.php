<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model\SearchResult\Area;

use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\InventoryInStorePickupApi\Model\SearchResult\Area\SearchTerm\HandlerInterface;
use Magento\InventoryInStorePickupApi\Model\SearchResult\StrategyInterface;

/**
 * Extract address data from Search Term.
 */
class HandleSearchTerm
{
    /**
     * @var array
     */
    private $handlers;
    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @param DataObjectFactory $dataObjectFactory
     * @param array $handlers
     */
    public function __construct(DataObjectFactory $dataObjectFactory, array $handlers = [])
    {
        $this->validate($handlers);
        $this->handlers = $handlers;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Validate input handlers input type.
     *
     * @param array $handlers
     */
    private function validate(array $handlers): void
    {
        foreach ($handlers as $handler) {
            if (!$handler instanceof HandlerInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Handler must implement %s.' .
                        '%s has been received instead.',
                        StrategyInterface::class,
                        get_class($handler)
                    )
                );
            }
        }
    }

    /**
     * @param string $searchTerm
     * @return DataObject
     */
    public function execute(string $searchTerm) : DataObject
    {
        $result = $this->dataObjectFactory->create();
        /** @var HandlerInterface $handler */
        foreach ($this->handlers as $handler) {
            $handler->execute($searchTerm, $result);
        }

        return $result;
    }
}
