<?php

namespace Modera\Component\SeleniumTools\Tests\Unit;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverOptions;
use Facebook\WebDriver\WebDriverWindow;
use Modera\Component\SeleniumTools\Actor;
use Modera\Component\SeleniumTools\ActorBrowserController;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../Fixtures/SleepFuncOverride.php';

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class ActorBrowserControllerTest extends TestCase
{
    private $actorMock;

    private $driverMock;

    /**
     * @var ActorBrowserController
     */
    private $ctr;

    public function setUp()
    {
        $this->driverMock = \Phake::mock(RemoteWebDriver::class);

        $this->actorMock = \Phake::mock(Actor::class);
        \Phake::when($this->actorMock)
            ->getDriver()
            ->thenReturn($this->driverMock)
        ;

        $this->ctr = new ActorBrowserController($this->actorMock);
    }

    public function testDoesBrowserNeedToBeLaunchedAutomatically()
    {
        \Phake::when($this->actorMock)
            ->isBehaviourEnabled(Actor::BHR_AUTO_START)
            ->thenReturn('nein!')
        ;

        $this->assertEquals('nein!', $this->ctr->doesBrowserNeedToBeLaunchedAutomatically());
    }

    public function testLaunchBrowser()
    {
        $this->ctr->launchBrowser('foo-url');

        \Phake::verify($this->driverMock)
            ->get('foo-url')
        ;
    }

    public function testDoesBrowserWindowNeedToBeMaximized()
    {
        \Phake::when($this->actorMock)
            ->isBehaviourEnabled(Actor::BHR_AUTO_MAXIMIZE)
            ->thenReturn('jaa!')
        ;

        $this->assertEquals('jaa!', $this->ctr->doesBrowserWindowNeedToBeMaximized());
    }

    public function testMaximizeBrowser()
    {
        $windowMock = \Phake::mock(WebDriverWindow::class);

        $wdoMock = \Phake::mock(WebDriverOptions::class);
        \Phake::when($wdoMock)
            ->window()
            ->thenReturn($windowMock)
        ;

        \Phake::when($this->driverMock)
            ->manage()
            ->thenReturn($wdoMock)
        ;

        $this->ctr->maximizeBrowser();

        \Phake::verify($windowMock)
            ->maximize()
        ;
    }

    public function testIsFocusingNeeded()
    {

    }
}