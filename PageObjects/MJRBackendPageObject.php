<?php

namespace Modera\Component\SeleniumTools\PageObjects;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Modera\Component\SeleniumTools\Querying\By;
use Modera\Component\SeleniumTools\Querying\ExtDeferredQueryHandler;

/**
 * Provides a high-level abstraction to most common actions you will need to perform when writing tests for MJR
 * backend (https://mjr.dev.modera.org/).
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class MJRBackendPageObject
{
    /**
     * @var RemoteWebDriver
     */
    private $driver;

    /**
     * @var ExtDeferredQueryHandler
     */
    private $deferredQueryHandler;

    /**
     * @param RemoteWebDriver $driver
     */
    public function __construct(RemoteWebDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param string $username
     */
    public function typeInUsername($username)
    {
        $el = $this->driver->findElement(By::named(['field', 'User ID']));
        $el->clear();
        $el->sendKeys($username);
    }

    /**
     * @param string $password
     */
    public function typeInPassword($password)
    {
        $el = $this->driver->findElement(By::named(['field', 'Password']));
        $el->clear();
        $el->sendKeys($password);
    }

    public function clickSignInButton()
    {
        $this->driver->findElement(By::named(['button', 'Sign in']))->click();
    }

    /**
     * @param string $username
     * @param string $password
     */
    public function login($username, $password)
    {
        $sleep = 500000; // half second

        $this->typeInUsername($username);
        usleep($sleep);
        $this->typeInPassword($password);
        usleep($sleep);
        $this->clickSignInButton();
        usleep($sleep);
    }

    /**
     * @param string $label
     */
    public function clickMenuItemWithLabel($label)
    {
        $this->driver->findElement($this->getDeferredQueryHandler()->extComponentDomId("tab[text=$label]"))->click();
    }

    /**
     * @param string $label
     */
    public function clickToolsSectionWithLabel($label)
    {
        $this->driver->findElement(
            $this->getDeferredQueryHandler()->extDataviewColumnWithValue('dataview', 'name', $label)
        )->click();
    }

    /**
     * @return ExtDeferredQueryHandler
     */
    private function getDeferredQueryHandler()
    {
        if (!$this->deferredQueryHandler) {
            $this->deferredQueryHandler = new ExtDeferredQueryHandler($this->driver);
        }

        return $this->deferredQueryHandler;
    }
}