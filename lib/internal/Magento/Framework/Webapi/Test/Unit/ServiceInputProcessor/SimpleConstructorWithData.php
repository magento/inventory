<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Webapi\Test\Unit\ServiceInputProcessor;

use Magento\Framework\Model\AbstractExtensibleModel;

class SimpleConstructorWithData extends AbstractExtensibleModel
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
