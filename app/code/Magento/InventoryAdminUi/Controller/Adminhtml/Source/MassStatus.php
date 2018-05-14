<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Controller\Adminhtml\Source;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\InventoryAdminUi\Ui\Component\MassAction\Filter;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * MassStatus Controller
 */
class MassStatus extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_InventoryApi::source';

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var Filter
     */
    private $massActionFilter;

    /**
     * @param Context $context
     * @param StockRepositoryInterface $sourceRepository
     * @param Filter $massActionFilter
     */
    public function __construct(
        Context $context,
        SourceRepositoryInterface $sourceRepository,
        Filter $massActionFilter
    ) {
        parent::__construct($context);
        $this->sourceRepository = $sourceRepository;
        $this->massActionFilter = $massActionFilter;
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        if ($this->getRequest()->isPost() !== true) {
            $this->messageManager->addErrorMessage(__('Wrong request.'));

            return $this->resultRedirectFactory->create()->setPath('*/*');
        }
        $status = (bool)$this->getRequest()->getParam('status');
        $updatedItemsCount = 0;
        foreach ($this->massActionFilter->getIds() as $sourceCode) {
            /** @var SourceInterface $source */
            try {
                $source = $this->sourceRepository->get($sourceCode);
                $source->setEnabled($status);
                $this->sourceRepository->save($source);
                $updatedItemsCount++;
            } catch (CouldNotDeleteException $e) {
                $errorMessage = __('[Source Code: %1] ', $source->getSourceCode()) . $e->getMessage();
                $this->messageManager->addErrorMessage($errorMessage);
            }
        }
        $this->messageManager->addSuccessMessage(
            __('A total of %1 record(s) have been updated.', $updatedItemsCount)
        );

        return $this->resultRedirectFactory->create()->setPath('*/*');
    }
}
