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
     *
     * @deprecated
     * @see IndexAlias::REPLICA
     */
    public const ALIAS_REPLICA = IndexAlias::REPLICA->value;

    /**
     * Main index alias
     *
     * @deprecated
     * @see IndexAlias::MAIN
     */
    public const ALIAS_MAIN = IndexAlias::MAIN->value;

    /**
     * @var IndexAlias
     */
    private IndexAlias $value;

    /**
     * @param string $value One of self::ALIAS_*
     * @throws LocalizedException
     */
    public function __construct(string $value)
    {
        try {
            $this->value = IndexAlias::from($value);
        } catch (\ValueError) {
            throw new LocalizedException(new Phrase('Wrong value %value for alias', ['value' => $value]));
        }
    }

    /**
     * Validates the alias value during construction, ensuring it matches predefined constants, or throws an exception.
     *
     * @return string One of self::ALIAS_*
     */
    public function getValue(): string
    {
        return $this->value->value;
    }
}
