<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySourceSelection\Model\Request;

use Magento\InventorySourceSelectionApi\Api\Data\AddressRequestInterface;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface;
use Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterface;
use Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory;

/**
 * @inheritdoc
 */
class InventoryRequest implements InventoryRequestInterface
{
    /**
     * @var int
     */
    private $stockId;

    /**
     * @var ItemRequestInterface[]
     */
    private $items;

    /**
     * @var ItemRequestInterfaceFactory
     */
    private $itemRequestFactory;

    /**
     * @var AddressRequestInterface
     */
    private $address;

    /**
     * InventoryRequest constructor.
     *
     * @param ItemRequestInterfaceFactory $itemRequestFactory
     * @param AddressRequestInterface $address
     * @param int $stockId
     * @param array $items
     */
    public function __construct(
        ItemRequestInterfaceFactory $itemRequestFactory,
        AddressRequestInterface $address,
        int $stockId,
        array $items
    ) {
        $this->stockId = $stockId;
        $this->itemRequestFactory = $itemRequestFactory;
        $this->address = $address;

        //TODO: Temporary fix for resolving issue with webApi (https://github.com/magento-engcom/msi/issues/1524)
        foreach ($items as $item) {
            if (false === $item instanceof ItemRequestInterface) {
                $this->items[] = $this->itemRequestFactory->create([
                    'sku' => $item['sku'],
                    'qty' => $item['qty']
                ]);
            } else {
                $this->items[] = $item;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getStockId(): int
    {
        return $this->stockId;
    }

    /**
     * @inheritdoc
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @inheritdoc
     */
    public function getAddress(): AddressRequestInterface
    {
        return $this->address;
    }
}
