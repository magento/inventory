<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Webapi\Test\Unit\ServiceInputProcessor;

use Magento\Framework\Api\ImmutableDtoInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

class SimpleImmutableWithData extends AbstractExtensibleModel implements ImmutableDtoInterface
{
    const ENTITY_ID = 'entity_id';
    const NAME = 'name';

    /**
     * @return int|null
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }
}
