<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest;

use Magento\Framework\GraphQl\Query\Resolver\Argument\AstConverter;
use Magento\Framework\GraphQl\Query\Resolver\Argument\Filter\Clause;

/**
 * Resolve Name Filter parameters.
 */
class Name implements ResolverInterface
{

    /**
     * @var AstConverter
     */
    private $astConverter;

    /**
     * @param AstConverter $astConverter
     */
    public function __construct(AstConverter $astConverter)
    {
        $this->astConverter = $astConverter;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        \Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface $searchRequestBuilder,
        string $fieldName,
        string $argumentName,
        array $argument
    ): \Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface {
        $nameFilter = $argument[$argumentName];
        /** @var Clause $filter */
        $filter = $this->astConverter->getClausesFromAst($fieldName, [$argumentName => $nameFilter]);
        $filter = current($filter);

        $value = $filter->getClauseValue();
        $value = is_array($value) ? implode(',', $value) : $value;

        return $searchRequestBuilder->setNameFilter($value, $filter->getClauseType());
    }
}
