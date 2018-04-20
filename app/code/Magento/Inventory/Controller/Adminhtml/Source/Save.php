<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Controller\Adminhtml\Source;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use Magento\Inventory\Model\Source\Validator\UniqueCodeValidator;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * Save Controller
 */
class Save extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_InventoryApi::source';

    /**
     * @var SourceInterfaceFactory
     */
    private $sourceFactory;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var SourceHydrator
     */
    private $sourceHydrator;

    /**
     * @var UniqueCodeValidator
     */
    private $uniqueCodeValidator;

    /**
     * @param Context $context
     * @param SourceInterfaceFactory $sourceFactory
     * @param SourceRepositoryInterface $sourceRepository
     * @param SourceHydrator $sourceHydrator
     * @param UniqueCodeValidator $codeValidator
     */
    public function __construct(
        Context $context,
        SourceInterfaceFactory $sourceFactory,
        SourceRepositoryInterface $sourceRepository,
        SourceHydrator $sourceHydrator,
        UniqueCodeValidator $codeValidator
    ) {
        parent::__construct($context);
        $this->sourceFactory = $sourceFactory;
        $this->sourceRepository = $sourceRepository;
        $this->sourceHydrator = $sourceHydrator;
        $this->uniqueCodeValidator = $codeValidator;
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $request = $this->getRequest();
        $requestData = $request->getPost()->toArray();

        if (!$request->isPost() || empty($requestData['general'])) {
            $this->messageManager->addErrorMessage(__('Wrong request.'));
            $this->processRedirectAfterFailureSave($resultRedirect);
            return $resultRedirect;
        }

        try {
            $source = $this->getSource($requestData['general']);
            $this->processSave($source, $requestData);

            $this->messageManager->addSuccessMessage(__('The Source has been saved.'));
            $this->processRedirectAfterSuccessSave($resultRedirect, $source->getSourceCode());
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('The Source does not exist.'));
            $this->processRedirectAfterFailureSave($resultRedirect);
        } catch (ValidationException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_session->setSourceFormData($requestData);
            $this->processRedirectAfterFailureSave($resultRedirect);
        } catch (CouldNotSaveException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_session->setSourceFormData($requestData);
            $this->processRedirectAfterFailureSave(
                $resultRedirect,
                $requestData['general'][SourceInterface::SOURCE_CODE] ?? null
            );
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not save Source.'));
            $this->_session->setSourceFormData($requestData);
            $this->processRedirectAfterFailureSave(
                $resultRedirect,
                $requestData['general'][SourceInterface::SOURCE_CODE] ?? null
            );
        }
        return $resultRedirect;
    }

    /**
     * @param SourceInterface $source
     * @param array $requestData
     * @return void
     */
    private function processSave(SourceInterface $source, array $requestData)
    {
        $source = $this->sourceHydrator->hydrate($source, $requestData);

        $this->_eventManager->dispatch(
            'controller_action_inventory_populate_source_with_data',
            [
                'request' => $this->getRequest(),
                'source' => $source,
            ]
        );

        $this->sourceRepository->save($source);

        $this->_eventManager->dispatch(
            'controller_action_inventory_source_save_after',
            [
                'request' => $this->getRequest(),
                'source' => $source,
            ]
        );
    }

    /**
     * @param Redirect $resultRedirect
     * @param string $sourceCode
     * @return void
     */
    private function processRedirectAfterSuccessSave(Redirect $resultRedirect, string $sourceCode)
    {
        if ($this->getRequest()->getParam('back')) {
            $resultRedirect->setPath('*/*/edit', [
                SourceInterface::SOURCE_CODE => $sourceCode,
                '_current' => true,
            ]);
        } elseif ($this->getRequest()->getParam('redirect_to_new')) {
            $resultRedirect->setPath('*/*/new', [
                '_current' => true,
            ]);
        } else {
            $resultRedirect->setPath('*/*/');
        }
    }

    /**
     * @param Redirect $resultRedirect
     * @param string|null $sourceCode
     * @return void
     */
    private function processRedirectAfterFailureSave(Redirect $resultRedirect, string $sourceCode = null)
    {
        if (null === $sourceCode) {
            $resultRedirect->setPath('*/*/new');
        } else {
            $resultRedirect->setPath('*/*/edit', [
                SourceInterface::SOURCE_CODE => $sourceCode,
                '_current' => true,
            ]);
        }
    }

    /**
     * Get existed source in case of update or create one in case of new source.
     *
     * @param array $sourceData
     * @return SourceInterface
     * @throws NoSuchEntityException in case requested source doesn't exist.
     * @throws ValidationException in case source code is not unique.
     */
    private function getSource(array $sourceData): SourceInterface
    {
        //Only existed sources have source code disabled.
        $isExistedSource = $sourceData['disable_source_code'] ?? false;
        if ($isExistedSource) {
            $source = $this->sourceRepository->get($sourceData[SourceInterface::SOURCE_CODE]);
        } else {
            $source = $this->sourceFactory->create();
            $source->setSourceCode($sourceData['source_code']);
            $validationResult = $this->uniqueCodeValidator->validate($source);
            if ($validationResult->getErrors()) {
                throw new ValidationException(
                    __(
                        'Source with code: "%code" already exists.',
                        ['code' => $source->getSourceCode()]
                    )
                );
            }
        }

        return $source;
    }
}
