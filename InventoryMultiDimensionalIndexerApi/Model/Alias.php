<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryMultiDimensionalIndexerApi\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Index Alias object
 *
 * @api
 */
class Alias
{
    /**
     * Replica index alias
     */
    public const ALIAS_REPLICA = 'replica';

    /**
     * Main index alias
     */
    public const ALIAS_MAIN = 'main';

    /**
     * One of self::ALIAS_*
     *
     * @var string
     */
    private $value;

    /**
     * @param string $value One of self::ALIAS_*
     * @throws LocalizedException
     */
    public function __construct(string $value)
    {
        if ($value !== self::ALIAS_REPLICA && $value !== self::ALIAS_MAIN) {
            throw new LocalizedException(new Phrase('Wrong value %value for alias', ['value' => $value]));
        }
        $this->value = $value;
    }

    /**
     * Validates the alias value during construction, ensuring it matches predefined constants, or throws an exception.
     *
     * @return string One of self::ALIAS_*
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
