<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area\SearchTerm;

use Magento\Directory\Model\Country\Postcode\ValidatorInterface;
use Magento\Framework\DataObject;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\Area\SearchTerm\ParserInterface;

/**
 * Extract postcode from search term if search term match postcode validation for store country.
 */
class PostcodeParser implements ParserInterface
{
    private const POSTCODE = 'postcode';
    private const COUNTRY = 'country';

    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var DelimiterParser
     */
    private $parser;

    /**
     * @param ValidatorInterface $validator
     * @param DelimiterParser $parser
     */
    public function __construct(ValidatorInterface $validator, DelimiterParser $parser)
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
        if ($this->validator->validate($searchQuery, $dataObject->getData(self::COUNTRY))) {
            $dataObject->setData(self::POSTCODE, $searchQuery);
        } else {
            $dataObject->setData(self::POSTCODE, '');
        }
    }
}
