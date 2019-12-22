<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\PickupLocation\Mapper\PreProcessor\FrontendDescription;

use Magento\Framework\Filter\Template;
use Magento\Framework\Filter\VariableResolverInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\UrlInterface;

/**
 * Filter store-pickup source short description.
 */
class Filter extends Template
{
    /**
     * @var VariableResolverInterface
     */
    private $variableResolver;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @param UrlInterface $url
     * @param StringUtils $string
     * @param VariableResolverInterface $variableResolver
     * @param array $variables
     * @param array $directiveProcessors
     */
    public function __construct(
        UrlInterface $url,
        StringUtils $string,
        VariableResolverInterface $variableResolver,
        $variables = [],
        $directiveProcessors = []
    ) {
        parent::__construct($string, $variables, $directiveProcessors, $variableResolver);
        $this->variableResolver = $variableResolver;
        $this->url = $url;
    }

    /**
     * Retrieve media file URL directive.
     *
     * @param string[] $construction
     * @return string
     */
    public function mediaDirective($construction): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $url = $this->processMediaUrl(html_entity_decode($construction[2], ENT_QUOTES));

        return $this->url->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]) . $url;
    }

    /**
     * Retrieve ulr param from given value.
     *
     * @param string $value
     * @return string
     */
    private function processMediaUrl(string $value): string
    {
        $tokenizer = new Template\Tokenizer\Parameter();
        $tokenizer->setString($value);
        $params = $tokenizer->tokenize();
        if (isset($params['url']) && substr($params['url'], 0, 1) === '$') {
            $params['url'] = $this->variableResolver->resolve(substr($value, 1), $this, $this->templateVars);
        }

        return $params['url'] ?? '';
    }
}
