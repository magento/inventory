<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 08.02.18
 * Time: 23:54
 */

namespace Magento\InventoryConfiguration\Model;

use Magento\Framework\Exception\LocalizedException;

class StockValidationComposer implements StockConfigurationInterface
{
    /**
     * @var StockConfigurationInterface[]
     */
    protected $validators;

    /**
     * StockValidationComposer constructor.
     * @param StockConfigurationInterface[] $validators
     * @throws LocalizedException
     */
    public function __construct(array $validators=[])
    {
        foreach ($validators as $validator) {
            if (!$validator instanceof StockConfigurationInterface) {
                throw new LocalizedException(
                    __('Validator must implement StockConfigurationInterface.')
                );
            }
        }
        $this->validators = $validators;
    }

    public function validate($sku, $stockId, $qtyWithReservation, $isSalable, $globalMinQty): bool
    {
        return true;
    }
}
