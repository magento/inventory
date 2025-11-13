<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryMultiDimensionalIndexerApi\Model;

/**
 * Aliases for inventory indexer.
 */
enum IndexAlias: string
{
    case MAIN = 'main';
    case REPLICA = 'replica';
}
