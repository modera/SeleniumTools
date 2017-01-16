<?php

namespace Modera\Component\SeleniumTools\Querying;

use Behat\Mink\Selector\NamedSelector;
use Facebook\WebDriver\WebDriverBy;

/**
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class By extends WebDriverBy
{
    /**
     * @var NamedSelector
     */
    private static $namedSelectorHandler;

    /**
     * For list of supported selector types see keys of NamedSelector::$selectors property.
     *
     * @param string $selector
     *
     * @return WebDriverBy
     */
    public static function named($selector)
    {
        return WebDriverBy::xpath(self::namedSelectorToXPath($selector));
    }

    /**
     * Convertes given CSS $selector to XPath
     *
     * @param string $selector
     * 
     * @return string
     */
    protected static function namedSelectorToXPath($selector)
    {
        if (!self::$namedSelectorHandler) {
            self::$namedSelectorHandler = new NamedSelector();
        }

        return self::$namedSelectorHandler->translateToXPath($selector);
    }
}