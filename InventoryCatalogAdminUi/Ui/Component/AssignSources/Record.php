<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Ui\Component\AssignSources;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\Ui\Component\Container;

/**
 * Assigned sources record.
 */
class Record extends Container
{
    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @param ContextInterface $context
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param UiComponentInterface[] $components
     * @param array $data
     * @param AuthorizationInterface|null $authorization
     */
    public function __construct(
        ContextInterface $context,
        DefaultStockProviderInterface $defaultStockProvider,
        array $components = [],
        array $data = [],
        ?AuthorizationInterface $authorization = null
    ) {
        parent::__construct($context, $components, $data);
        $this->defaultStockProvider = $defaultStockProvider;
        $this->authorization = $authorization ?? ObjectManager::getInstance()->get(AuthorizationInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function prepare()
    {
        parent::prepare();

        $deleteConfig = $this->components['actionDelete']->getData('config');
        // Disable assign sources if stock is default
        $stockId = (int) $this->context->getRequestParam(StockInterface::STOCK_ID);
        if ($stockId === $this->defaultStockProvider->getId()) {
            $deleteConfig['disabled'] = true;
            $deleteConfig['notice'] = __('Disabled for default stock');
        }

        // Hide delete assigned sources button according to ACL resource.
        $deleteConfig['visible'] = $this->authorization->isAllowed('Magento_InventoryApi::stock_source_link');

        $this->components['actionDelete']->setData('config', $deleteConfig);
    }
}
