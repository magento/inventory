<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDistanceBasedSourceSelectionApi\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Exception to be thrown when a non existing LatsLngs from address provider is requested.
 *
 * @api
 */
class NoSuchLatsLngsFromAddressProviderException extends LocalizedException
{

}
