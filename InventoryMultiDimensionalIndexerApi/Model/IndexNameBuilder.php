<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryMultiDimensionalIndexerApi\Model;

use Magento\Framework\ObjectManagerInterface;

/**
 * Index Name builder. It is Facade for simplifying IndexName object creation
 *
 * @api
 */
class IndexNameBuilder
{
    /**
     * Index id parameter name. Used internally in this object
     *
     * Can not replace on private constant (feature of PHP 7.1) because we need to support PHP 7.0
     * @var string
     */
    private static $indexId = 'indexId';

    /**
     * Dimensions parameter name. Used internally in this object
     *
     * Can not replace on private constant (feature of PHP 7.1) because we need to support PHP 7.0
     *
     * @var string
     */
    private static $dimensions = 'dimensions';

    /**
     * Alias parameter name. Used internally in this object
     *
     * Can not replace on private constant (feature of PHP 7.1) because we need to support PHP 7.0
     *
     * @var string
     */
    private static $alias = 'alias';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var DimensionFactory
     */
    private $dimensionFactory;

    /**
     * @var AliasFactory
     */
    private $aliasFactory;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @param ObjectManagerInterface $objectManager
     * @param DimensionFactory $dimensionFactory
     * @param AliasFactory $aliasFactory
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        DimensionFactory $dimensionFactory,
        AliasFactory $aliasFactory
    ) {
        $this->objectManager = $objectManager;
        $this->dimensionFactory = $dimensionFactory;
        $this->aliasFactory = $aliasFactory;
    }

    /**
     * Sets the index ID for the builder, storing it in the internal data array for use in building the IndexName object
     *
     * @param string $indexId
     * @return self
     */
    public function setIndexId(string $indexId): self
    {
        $this->data[self::$indexId] = $indexId;
        return $this;
    }

    /**
     * Adds a dimension to the builder, creating a new Dimension object using the provided name and value
     *
     * @param string $name
     * @param string $value
     * @return self
     */
    public function addDimension(string $name, string $value): self
    {
        $this->data[self::$dimensions][] = $this->dimensionFactory->create([
            'name' => $name,
            'value' => $value,
        ]);
        return $this;
    }

    /**
     * Sets the alias for the builder by creating an Alias object and storing it in the internal data array.
     *
     * @param string $alias
     * @return self
     */
    public function setAlias(string $alias): self
    {
        $this->data[self::$alias] = $this->aliasFactory->create(['value' => $alias]);
        return $this;
    }

    /**
     * Builds an `IndexName` object using stored data, resets the internal data array, and returns the created object.
     *
     * @return IndexName
     */
    public function build(): IndexName
    {
        $indexName = $this->objectManager->create(IndexName::class, $this->data);
        $this->data = [];
        return $indexName;
    }
}
