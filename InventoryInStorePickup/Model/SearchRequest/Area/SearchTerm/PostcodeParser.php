<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area\SearchTerm;

use Magento\Directory\Model\Country\Postcode\ValidatorInterface;
use Magento\Framework\DataObject;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\Area\SearchTerm\DelimiterConfig;
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
     * @var DelimiterConfig
     */
    private $delimiterConfig;

    /**
     * @param ValidatorInterface $validator
     * @param DelimiterConfig $delimiterConfig
     */
    public function __construct(ValidatorInterface $validator, DelimiterConfig $delimiterConfig)
    {
        $this->validator = $validator;
        $this->delimiterConfig = $delimiterConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $searchTerm, DataObject $dataObject): void
    {
        $searchQuery = $this->getSearchQuery($searchTerm);
        if ($this->validator->validate($searchQuery, $dataObject->getData(self::COUNTRY))) {
            $dataObject->setData(self::POSTCODE, $searchQuery);
        } else {
            $dataObject->setData(self::POSTCODE, '');
        }
    }

    /**
     * Parse and return search query from search term.
     *
     * @param string $searchTerm
     *
     * @return string
     */
    private function getSearchQuery(string $searchTerm): string
    {
        if (strpos($searchTerm, $this->delimiterConfig->getDelimiter()) === false) {
            return $searchTerm;
        }
        $searchTerm = explode($this->delimiterConfig->getDelimiter(), $searchTerm);

        return trim(current($searchTerm));
    }
}
