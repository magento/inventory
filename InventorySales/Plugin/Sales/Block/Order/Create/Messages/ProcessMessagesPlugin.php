<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\Sales\Block\Order\Create\Messages;

use Magento\Sales\Block\Adminhtml\Order\Create\Messages;

/**
 * Remove duplicated error messages from order create message block.
 */
class ProcessMessagesPlugin
{
    private const ITEMS_GRID = 'items_grid';

    /**
     * Remove massage from create order message block in case it is presented in items grid.
     *
     * @param Messages $subject
     * @return void
     */
    public function beforeToHtml(Messages $subject): void
    {
        $itemsBlock = $subject->getLayout()->getBlock(self::ITEMS_GRID);
        if (!$itemsBlock) {
            return;
        }

        $items = $itemsBlock->getItems();
        foreach ($items as $item) {
            if ($item->getHasError()) {
                $messageCollection = $subject->getMessageCollection();
                foreach ($messageCollection->getItems() as $blockMessage) {
                    if ($item->getMessage(true) === $blockMessage->getText()) {
                        /* Remove duplicated messages.*/
                        $messageCollection->deleteMessageByIdentifier($blockMessage->getIdentifier());
                    }
                }
                $subject->setMessages($messageCollection);
            }
        }
    }
}
