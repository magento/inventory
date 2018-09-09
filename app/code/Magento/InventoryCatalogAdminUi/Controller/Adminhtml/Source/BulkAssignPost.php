<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Controller\Adminhtml\Source;

use Magento\AsynchronousOperations\Model\MassSchedule;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\InventoryCatalogAdminUi\Model\BulkSessionProductsStorage;

class BulkAssignPost extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * @var BulkSessionProductsStorage
     */
    private $bulkSessionProductsStorage;

    /**
     * @var MassSchedule
     */
    private $massSchedule;

    /**
     * @var \Magento\Backend\Model\Auth
     */
    private $authSession;

    /**
     * @param Action\Context $context
     * @param BulkSessionProductsStorage $bulkSessionProductsStorage
     * @param MassSchedule $massSchedule
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        Action\Context $context,
        BulkSessionProductsStorage $bulkSessionProductsStorage,
        MassSchedule $massSchedule
    ) {
        parent::__construct($context);
        $this->bulkSessionProductsStorage = $bulkSessionProductsStorage;
        $this->massSchedule = $massSchedule;
        $this->authSession = $context->getAuth();
    }

    /**
     * @param array $skus
     * @param array $sourceCodes
     * @return array
     */
    private function createEntities(array $skus, array $sourceCodes): array
    {
        $entities = [];

        foreach ($skus as $sku) {
            foreach ($sourceCodes as $sourceCode) {
                $entities[] = [
                    'sku' => $sku,
                    'sourceCode' => $sourceCode,
                ];
            }
        }

        return $entities;
    }

    /**
     * @inheritdoc
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\BulkException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $sourceCodes = $this->getRequest()->getParam('sources', []);
        $skus = $this->bulkSessionProductsStorage->getProductsSkus();

        $userId = (int) $this->authSession->getUser()->getId();

        $entities = $this->createEntities($skus, $sourceCodes);
        $this->massSchedule->publishMass(
            'async.V1.inventory.product-source-assign.POST',
            $entities,
            null,
            $userId
        );

        $this->messageManager->addSuccessMessage(
            __('Your bulk operation request was successfully enqueued. You will receive a notification when done.')
        );

        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $result->setPath('catalog/product/index');
    }
}
