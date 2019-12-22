<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model\SearchRequest\Area;

use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\Area\SearchTerm\ParserInterface;

/**
 * Extract address data from Search Term.
 * @api
 */
class Pipeline
{
    /**
     * @var ParserInterface[]
     */
    private $parsers;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @param DataObjectFactory $dataObjectFactory
     * @param array $parsers
     */
    public function __construct(DataObjectFactory $dataObjectFactory, array $parsers = [])
    {
        $this->validate($parsers);
        $this->parsers = $parsers;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Validate input parsers type.
     *
     * @param array $parsers
     */
    private function validate(array $parsers): void
    {
        foreach ($parsers as $parser) {
            if (!$parser instanceof ParserInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Parser must implement %s.' .
                        '%s has been received instead.',
                        ParserInterface::class,
                        get_class($parser)
                    )
                );
            }
        }
    }

    /**
     * @param string $searchTerm
     * @return DataObject
     */
    public function execute(string $searchTerm) : DataObject
    {
        $result = $this->dataObjectFactory->create();
        foreach ($this->parsers as $parser) {
            $parser->execute($searchTerm, $result);
        }

        return $result;
    }
}
