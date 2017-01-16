<?php

namespace Modera\Component\SeleniumTools\Querying;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Modera\Component\SeleniumTools\Exceptions\NoElementFoundException;

/**
 * Contains ExtJs related query methods.
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class ExtDeferredQueryHandler
{
    /**
     * @var RemoteWebDriver
     */
    private $driver;

    /**
     * @param RemoteWebDriver $driver
     */
    public function __construct(RemoteWebDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Attempts to resolve DOM id of component which matches Ext.ComponentQuery.query() compatible $query
     *
     * @param string $query  ExtJs Ext.ComponentQuery.query() method compatible query
     * @param int $timeout  How long to wait for a component with $query to become discoverable
     *
     * @return string  DOM element ID that represents a first component resolved by given $query
     */
    public function extComponentDomId($query, $timeout = 30)
    {
        $startTime = time();

        // although ExtJs has already generated DOM element ID it may not be rendered yet as of now ...
        $id = $this->doRunWhenComponentAvailable(
            $query,
            'return result[0].id;',
            $startTime,
            $timeout
        );

        $id = WebDriverBy::id($id);

        // so we are waiting for some time until ExtJs has generated requred Dom and flushed it so
        // we can really access and manipulate it
        $this->driver->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated($id));

        return $id;
    }

    /**
     * Runs $stmt when a Ext.ComponentQuery.query() compatible $query returns at least one match.
     *
     * @param string $query  ExtJS Ext.ComponentQuery.query() compatible query
     * @param string $stmt  A JavaScript statement that needs to be executed when at least one component is returned by
     *                      the $query. You can access to returned components using "result" variable.
     * @param int $timeout  Maximum wait time for at least one component to become available
     *
     * @return string
     */
    public function runWhenComponentAvailable($query, $stmt, $timeout = 30)
    {
        return $this->doRunWhenComponentAvailable($query, $stmt, time(), $timeout);
    }

    /**
     * @param string $query
     * @param string $fieldName
     * @param string $fieldValue
     * @param int $timeout
     *
     * @return string
     */
    public function extGridColumnWithValue($query, $fieldName, $fieldValue, $timeout = 30)
    {
        $stmt = <<<'JST'
    var grid = result[0];
        
    var index = grid.getStore().findExact("%s", "%s");
    if (-1 != index) {
        return grid.getView().getNode(index).id;
    }
JST;

        $stmt = sprintf($stmt, $fieldName, $fieldValue);

        $startTime = time();

        return WebDriverBy::id($this->doRunWhenComponentAvailable($query, $stmt, $startTime, $timeout));
    }

    /**
     * @param string $query
     * @param string $fieldName
     * @param string $fieldValue
     * @param int $timeout
     *
     * @return string
     */
    public function extDataviewColumnWithValue($query, $fieldName, $fieldValue, $timeout = 30)
    {
        $stmt = <<<'JST'
    var dataView = result[0];
        
    var index = dataView.getStore().findExact("%s", "%s");
    if (-1 != index) {
        return dataView.getNode(index).id;
    }
JST;

        $stmt = sprintf($stmt, $fieldName, $fieldValue);

        $startTime = time();

        return WebDriverBy::id($this->doRunWhenComponentAvailable($query, $stmt, $startTime, $timeout));
    }

    private function doRunWhenComponentAvailable($query, $stmt, $startTime, $timeout = 30)
    {
        // If we return a boolean value from a function then we will get
        // "java.lang.Boolean cannot be cast to java.lang.String" exception by Selenium, so to address this issue
        // we are returning a 'false' as a string instead
        $js = <<<'JST'
%function_name% = function () {
    var result = Ext.ComponentQuery.query("%query%");
    if (result.length > 0) {
        %stmt%
    }
    
    return 'false';
};
JST;
        $functionName = 'edq_'.uniqid();

        $js = str_replace(
            ['%function_name%', '%query%', '%stmt%'],
            [$functionName, addslashes($query), $stmt],
            $js
        );

        // publishing a function once and later just invoking it instead of re-declaring it each time
        $this->driver->executeScript($js);

        while (true) {
            $value = $this->driver->executeScript("return window.$functionName();"); // invoking previously published function

            if ('false' !== $value) {
                // function is no longer needed so we are removing it from the browser
                $this->driver->executeScript("delete window.$functionName;");

                return $value;
            }

            if ((time() - $startTime) > $timeout) {
                throw new NoElementFoundException(sprintf(
                    'Unable to locate element with ExtJs query "%s" (waited for %d seconds).', $query, $timeout
                ));
            }
        }
    }
}