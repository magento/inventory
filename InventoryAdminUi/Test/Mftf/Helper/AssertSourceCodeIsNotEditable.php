<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

namespace Magento\InventoryAdminUi\Test\Mftf\Helper;

use Magento\FunctionalTestingFramework\Helper\Helper;
use Magento\FunctionalTestingFramework\Page\Handlers\SectionObjectHandler;

/**
 * Class AssertSourceCodeIsNotEditable verifies Source Code is not editable.
 */
class AssertSourceCodeIsNotEditable extends Helper
{
    /**
     * Assert that Source Code is not editable.
     *
     * @return void
     */
    public function assertSourceCode()
    {
        try {
            /** @var \Magento\FunctionalTestingFramework\Module\MagentoWebDriver $webDriver */
            $webDriver = $this->getModule('\Magento\FunctionalTestingFramework\Module\MagentoWebDriver');
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
        $selector = "(//span[@class='data-grid-cell-content'][text()='Code'])";
        $selector .= "/../../../..//div[@class='data-grid-cell-content']";
        //phpstan:ignore
        $element = $webDriver->webDriver->findElement(\Facebook\WebDriver\WebDriverBy::xpath($selector));
        $ableToEditSourceCode = true;
        try {
            $element->sendKeys('test value');
        } catch (\Exception $e) {
            $ableToEditSourceCode = false;
        }
        if ($ableToEditSourceCode) {
            $this->fail('There is ability to edit source code.');
        }
    }
}
