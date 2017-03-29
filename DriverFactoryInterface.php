<?php

namespace Modera\Component\SeleniumTools;

use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * Implementations are responsible for creating instances of Selenium RemoteWebDriver which later are manipulated
 * by Actors.
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
interface DriverFactoryInterface
{
    /**
     * @param Actor $actor
     *
     * @return RemoteWebDriver
     */
    public function createDriver(Actor $actor);
}