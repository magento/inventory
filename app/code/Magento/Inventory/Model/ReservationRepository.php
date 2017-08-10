<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Inventory\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Inventory\Model\Reservation\Command\GetListInterface;
use Magento\InventoryApi\Api\ReservationRepositoryInterface;

/**
 * @inheritdoc
 */
class ReservationRepository implements ReservationRepositoryInterface
{
    /**
     * @var GetListInterface
     */
    private $commandGetList;

    /**
     * @param GetListInterface $commandGetList
     */
    public function __construct(
        GetListInterface $commandGetList
    ) {
        $this->commandGetList = $commandGetList;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria = null)
    {
        return $this->commandGetList->execute($searchCriteria);
    }
}
