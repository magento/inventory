<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Controller\Adminhtml\Source\Save;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\EntityManager\EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use Magento\Inventory\Controller\Adminhtml\Source\SourceHydrator;
use Magento\Inventory\Model\Source\Validator\UniqueCodeValidator;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;

/**
 * Process source validation and save.
 */
class ProcessSave
{
    /**
     * @var SourceHydrator
     */
    private $sourceHydrator;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var UniqueCodeValidator
     */
    private $uniqueCodeValidator;

    /**
     * @var SourceInterfaceFactory
     */
    private $sourceFactory;

    /**
     * @param SourceHydrator $sourceHydrator
     * @param EventManager $eventManager
     * @param SourceRepositoryInterface $sourceRepository
     * @param UniqueCodeValidator $uniqueCodeValidator
     * @param SourceInterfaceFactory $sourceFactory
     */
    public function __construct(
        SourceHydrator $sourceHydrator,
        EventManager $eventManager,
        SourceRepositoryInterface $sourceRepository,
        UniqueCodeValidator $uniqueCodeValidator,
        SourceInterfaceFactory $sourceFactory
    ) {
        $this->sourceHydrator = $sourceHydrator;
        $this->eventManager = $eventManager;
        $this->sourceRepository = $sourceRepository;
        $this->uniqueCodeValidator = $uniqueCodeValidator;
        $this->sourceFactory = $sourceFactory;
    }

    /**
     * Validate and save source.
     *
     * @param RequestInterface $request
     * @return SourceInterface
     * @throws NoSuchEntityException in case requested source doesn't exist.
     * @throws ValidationException in case source code is not unique.
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function execute(RequestInterface $request): SourceInterface
    {
        $requestData = $request->getPost()->toArray();
        $source = $this->getSource($requestData['general']);

        return $this->processSave($source, $request, $requestData);
    }

    /**
     * Save source.
     *
     * @param SourceInterface $source
     * @param RequestInterface $request
     * @param array $requestData
     * @return SourceInterface
     * @throws ValidationException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function processSave(
        SourceInterface $source,
        RequestInterface $request,
        array $requestData
    ): SourceInterface {
        $source = $this->sourceHydrator->hydrate($source, $requestData);

        $this->eventManager->dispatch(
            'controller_action_inventory_populate_source_with_data',
            [
                'request' => $request,
                'source' => $source,
            ]
        );

        $this->sourceRepository->save($source);

        $this->eventManager->dispatch(
            'controller_action_inventory_source_save_after',
            [
                'request' => $request,
                'source' => $source,
            ]
        );

        return $source;
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
