<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShippingAdminUi\Controller\Adminhtml\SourceSelection;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\InventoryShippingAdminUi\Model\SourceSelectionResultAdapterFromRequestItemsFactory;
use Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory;

/**
 * ProcessAlgorithm Controller
 */
class ProcessAlgorithm extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_InventoryApi::source';

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var ItemRequestInterfaceFactory
     */
    private $itemRequestFactory;

    /**
     * @var SourceSelectionResultAdapterFromRequestItemsFactory
     */
    private $sourceAdapterFromRequestItemsFactory;

    /**
     * @param Context $context
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param ItemRequestInterfaceFactory $itemRequestFactory
     * @param SourceSelectionResultAdapterFromRequestItemsFactory $sourceAdapterFromRequestItemsFactory
     */
    public function __construct(
        Context $context,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        ItemRequestInterfaceFactory $itemRequestFactory,
        SourceSelectionResultAdapterFromRequestItemsFactory $sourceAdapterFromRequestItemsFactory
    ) {
        parent::__construct($context);
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->itemRequestFactory = $itemRequestFactory;
        $this->sourceAdapterFromRequestItemsFactory = $sourceAdapterFromRequestItemsFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $request = $this->getRequest();
        $postRequest = $request->getPost()->toArray();

        if ($request->isPost() && !empty($postRequest['requestData'])) {
            $requestData = $postRequest['requestData'];
            $algorithmCode = $postRequest['algorithmCode'];

            //TODO: maybe need to add exception when websiteId empty
            $websiteId = $postRequest['websiteId'] ?? 1;
            $stockId = (int)$this->stockByWebsiteIdResolver->execute((int)$websiteId)->getStockId();
            $result = $requestItems = [];

            foreach ($requestData as $data) {
                $requestItems[] = $this->itemRequestFactory->create([
                    'sku' => $data['sku'],
                    'qty' => $data['qty']
                ]);
            }

            $sourceAdapter = $this->sourceAdapterFromRequestItemsFactory
                ->create($stockId, $requestItems, $algorithmCode);

            foreach ($requestData as $data) {
                $orderItem = $data['orderItem'];
                $result[$orderItem] = $sourceAdapter->getSkuSources($data['sku']);
            }

            $result['sourceCodes'] = $sourceAdapter->getSources();

            $resultJson->setData($result);
        }

        return $resultJson;
    }
}
