<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area\SearchTerm;

use Magento\Directory\Model\Country\Postcode\ValidatorInterface;
use Magento\Framework\DataObject;
use Magento\InventoryInStorePickupApi\Model\SearchResult\Area\SearchTerm\HandlerInterface;

/**
 * Extract postcode from search term if search term match postcode validation for store country.
 */
class PostCodeHandler implements HandlerInterface
{
    public const POSTCODE = 'postcode';

    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param ValidatorInterface $validator
     * @param Parser $parser
     */
    public function __construct(ValidatorInterface $validator, Parser $parser)
    {
        $this->validator = $validator;
        $this->parser = $parser;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $searchTerm, DataObject $dataObject): void
    {
        $searchQuery = $this->parser->getSearchQuery($searchTerm);
        if ($this->validator->validate($searchQuery, $dataObject->getData(CountryHandler::COUNTRY))) {
            $dataObject->setData(self::POSTCODE, $searchQuery);
        } else {
            $dataObject->setData(self::POSTCODE, '');
        }
    }
}
