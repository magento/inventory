<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Class OrderIsNotReadyForPickupException
 *
 * @package Magento\InventoryInStorePickupApi\Exception
 * @api
 */
class OrderIsNotReadyForPickupException extends LocalizedException
{
    /**
     * @param \Magento\Framework\Phrase $phrase
     * @param \Exception                $cause
     * @param int                       $code
     */
    public function __construct(Phrase $phrase = null, \Exception $cause = null, $code = 0)
    {
        if ($phrase === null) {
            $phrase = new Phrase('The order is not ready for pickup');
        }

        parent::__construct($phrase, $cause, $code);
    }
}
