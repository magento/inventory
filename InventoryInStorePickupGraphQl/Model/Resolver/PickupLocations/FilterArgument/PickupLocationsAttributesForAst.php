<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\FilterArgument;

use Magento\Framework\GraphQl\Config\Element\Type;
use Magento\Framework\GraphQl\ConfigInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\FieldEntityAttributesInterface;

/**
 * @inheritdoc
 */
class PickupLocationsAttributesForAst implements FieldEntityAttributesInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var array
     */
    private $additionalAttributes = [];

    /**
     * @param ConfigInterface $config
     * @param array $additionalAttributes
     */
    public function __construct(
        ConfigInterface $config,
        array $additionalAttributes = []
    ) {
        $this->config = $config;
        $this->additionalAttributes = array_merge($this->additionalAttributes, $additionalAttributes);
    }

    /**
     * @inheritdoc
     */
    public function getEntityAttributes(): array
    {
        $pickupLocationTypeSchema = $this->config->getConfigElement('PickupLocation');
        if (!$pickupLocationTypeSchema instanceof Type) {
            throw new \LogicException(__("PickupLocation type not defined in schema."));
        }

        $fields = [];
        foreach ($pickupLocationTypeSchema->getFields() as $field) {
            $fields[$field->getName()] = [
                'type' => 'String',
                'fieldName' => $field->getName(),
            ];
        }

        foreach ($this->additionalAttributes as $attribute) {
            $fields[$attribute] = [
                'type' => 'String',
                'fieldName' => $attribute,
            ];
        }

        return $fields;
    }
}
