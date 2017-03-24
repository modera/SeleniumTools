<?php

namespace Modera\Component\SeleniumTools\Tests\Unit;

use Facebook\WebDriver\Remote\RemoteTargetLocator;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverAlert;
use Modera\Component\SeleniumTools\Actor;
use Modera\Component\SeleniumTools\ActorBrowserController;
use Modera\Component\SeleniumTools\TestHarness;
use Modera\Component\SeleniumTools\Tests\Fixtures\ActorWithCallbacks;

require_once __DIR__.'/../Fixtures/SleepFuncOverride.php';

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class ActorTest extends \PHPUnit_Framework_TestCase
{
    private $mockHarness;

    /**
     * @var Actor
     */
    private $actor;

    public function setUp()
    {
        $this->mockHarness = \Phake::mock(TestHarness::class);

        $this->actor = new Actor('bob', 'http://example.com', $this->mockHarness);
    }

    public function testConstruсtedInstance()
    {
        $this->assertEquals('bob', $this->actor->getName());
        $this->assertEquals('http://example.com', $this->actor->getStartUrl());
        $this->assertSame($this->mockHarness, $this->actor->getHarness());
    }

    public function testManagingBehaviours()
    {
        $this->assertTrue($this->actor->isBehaviourEnabled(Actor::BHR_AUTO_FOCUS));
        $this->assertTrue($this->actor->isBehaviourEnabled(Actor::BHR_AUTO_MAXIMIZE));
        $this->assertTrue($this->actor->isBehaviourEnabled(Actor::BHR_AUTO_START));

        $this->actor->disableBehaviour(Actor::BHR_AUTO_FOCUS);
        $this->assertFalse($this->actor->isBehaviourEnabled(Actor::BHR_AUTO_FOCUS));

        $this->actor->enableBehaviour(Actor::BHR_AUTO_FOCUS);
        $this->assertTrue($this->actor->isBehaviourEnabled(Actor::BHR_AUTO_FOCUS));

        $this->actor->setEnabledBehaviours([]);
        $this->assertFalse($this->actor->isBehaviourEnabled(Actor::BHR_AUTO_FOCUS));
        $this->assertFalse($this->actor->isBehaviourEnabled(Actor::BHR_AUTO_MAXIMIZE));
        $this->assertFalse($this->actor->isBehaviourEnabled(Actor::BHR_AUTO_START));
    }

    public function testDealingWithDriver()
    {
        $driverWannaBe = new \stdClass();

        $actor = new ActorWithCallbacks('bob', 'http://example.com', $this->mockHarness);

        \Phake::when($this->mockHarness)
            ->createDriver($actor)
            ->thenReturn($driverWannaBe)
        ;

        $this->assertFalse($actor->isDriverCreated());

        $driver = $actor->getDriver();
        $anotherDriver = $actor->getDriver();

        $this->assertSame($driverWannaBe, $driver);
        $this->assertSame($driver, $anotherDriver, 'Driver must have been runtime cached.');

        $this->assertTrue($actor->isDriverCreated());
    }

    public function testRunningInActor()
    {
        $driverMock = \Phake::mock(RemoteWebDriver::class);
        $controllerMock = \Phake::mock(ActorBrowserController::class);

        $additionalArg1 = new \stdClass();
        $additionalArg2 = new \stdClass();

        $actor = new ActorWithCallbacks('bob', 'http://example.com', $this->mockHarness);

        $this->trainControllerToLaunchBrowserAndCreateDriver($controllerMock, $actor);
        $this->mockDriverAndControllerMethods($actor, $controllerMock);

        \Phake::when($this->mockHarness)
            ->createDriver($actor)
            ->thenReturn($driverMock)
        ;

        \Phake::when($this->mockHarness)
            ->getAdditionalActorArgumentsFactory()
            ->thenReturn(function() use($additionalArg1, $additionalArg2) {
                return [$additionalArg1, $additionalArg2];
            })
        ;

        $actor->getControllerCallback = function() use($controllerMock) {
            return $controllerMock;
        };

        $isCalled = false;
        $invocationArgs = array();

        $runCallback = function($driver, $actor, $arg1, $arg2) use(&$isCalled, &$invocationArgs) {
            $isCalled = true;

            $invocationArgs[] = array(
                'driver' => $driver,
                'actor' => $actor,
                'arg1' => $arg1,
                'arg2' => $arg2,
            );
        };

        $actor->run($runCallback);

        $this->assertTrue($isCalled);
        $this->assertEquals(1, count($invocationArgs));
        $this->assertSame($driverMock, $invocationArgs[0]['driver']);
        $this->assertSame($actor, $invocationArgs[0]['actor']);
        $this->assertSame($additionalArg1, $invocationArgs[0]['arg1']);
        $this->assertSame($additionalArg2, $invocationArgs[0]['arg2']);

        \Phake::verify($controllerMock)
            ->launchBrowser('http://example.com')
        ;

        \Phake::verify($this->mockHarness)
            ->setActiveActor($actor)
        ;

        // Now we are checking that additional args were cached:

        $actor->run($runCallback);

        $this->assertEquals(2, count($invocationArgs));
        $this->assertSame($additionalArg1, $invocationArgs[1]['arg1']);
        $this->assertSame($additionalArg2, $invocationArgs[1]['arg2']);
    }

    /**
     * @expectedException \Modera\Component\SeleniumTools\Exceptions\ActorExecutionException
     * @expectedExceptionMessage An error occurred when executing "bob" actor: Foobar
     */
    public function testRunningInActorWithException()
    {
        $driverMock = \Phake::mock(RemoteWebDriver::class);
        $controllerMock = \Phake::mock(ActorBrowserController::class);

        $actor = new ActorWithCallbacks(
            'bob',
            'http://example.com',
            $this->mockHarness
        );

        $actor->getControllerCallback = function() use($controllerMock) {
            return $controllerMock;
        };

        \Phake::when($this->mockHarness)
            ->createDriver($actor)
            ->thenReturn($driverMock)
        ;

        $this->trainControllerToLaunchBrowserAndCreateDriver($controllerMock, $actor);

        $runCallback = function() {
            throw new \RuntimeException('Foobar');
        };

        $actor->run($runCallback);
    }

    public function testRunningWithMaximizedWindow()
    {
        $driverMock = \Phake::mock(RemoteWebDriver::class);
        $controllerMock = \Phake::mock(ActorBrowserController::class);

        $actor = new ActorWithCallbacks(
            'bob',
            'http://example.com',
            $this->mockHarness
        );

        $actor->getControllerCallback = function () use ($controllerMock) {
            return $controllerMock;
        };

        \Phake::when($this->mockHarness)
            ->createDriver($actor)
            ->thenReturn($driverMock)
        ;

        $this->trainControllerToLaunchBrowserAndCreateDriver($controllerMock, $actor);

        \Phake::when($controllerMock)
            ->doesBrowserWindowNeedToBeMaximized()
            ->thenReturn(true)
        ;

        $actor->run(function() {});

        \Phake::verify($controllerMock)
            ->maximizeBrowser()
        ;
    }

    public function testRunningWithFocusing()
    {
        $driverMock = \Phake::mock(RemoteWebDriver::class);
        $controllerMock = \Phake::mock(ActorBrowserController::class);

        $actor = new ActorWithCallbacks(
            'bob',
            'http://example.com',
            $this->mockHarness
        );

        $this->trainControllerToLaunchBrowserAndCreateDriver($controllerMock, $actor);

        $actor->getControllerCallback = function () use ($controllerMock) {
            return $controllerMock;
        };

        \Phake::when($controllerMock)
            ->isFocusingNeeded()
            ->thenReturn(true)
        ;

        \Phake::when($this->mockHarness)
            ->createDriver($actor)
            ->thenReturn($driverMock)
        ;

        $alertMock = \Phake::mock(WebDriverAlert::class);

        $rtlMock = \Phake::mock(RemoteTargetLocator::class);
        \Phake::when($rtlMock)
            ->alert()
            ->thenReturn($alertMock)
        ;

        \Phake::when($driverMock)
            ->switchTo()
            ->thenReturn($rtlMock)
        ;

        $actor->run(function() {});

        \Phake::verify($driverMock)
            ->executeScript($this->anything())
        ;
    }

    public function testKillingActor()
    {
        $controllerMock = \Phake::mock(ActorBrowserController::class);
        $driverMock = \Phake::mock(RemoteWebDriver::class);

        $actor = new ActorWithCallbacks(
            'bob',
            'http://example.com',
            $this->mockHarness
        );

        \Phake::when($this->mockHarness)
            ->createDriver($actor)
            ->thenReturn($driverMock)
        ;

        $this->trainControllerToLaunchBrowserAndCreateDriver($controllerMock, $actor);
        $this->mockDriverAndControllerMethods($actor, $controllerMock);

        $reflActor = new \ReflectionClass(Actor::class);
        $reflDriverProp = $reflActor->getProperty('driver');
        $reflDriverProp->setAccessible(true);

        $this->assertNull($reflDriverProp->getValue($actor));

        $actor->getDriver();

        $this->assertNotNull($reflDriverProp->getValue($actor));

        $actor->kill();
        \Phake::verify($driverMock)
            ->quit()
        ;
        $this->assertNull($reflDriverProp->getValue($actor));
    }

    private function trainControllerToLaunchBrowserAndCreateDriver($controllerMock, $actor)
    {
        \Phake::when($controllerMock)
            ->doesBrowserNeedToBeLaunchedAutomatically()
            ->thenReturn(true)
        ;

        \Phake::when($controllerMock)
            ->launchBrowser($this->anything())
            ->thenReturnCallback(function () use ($actor) {
                $actor->getDriver();
            })
        ;
    }

    private function mockDriverAndControllerMethods($actor, $controllerMock)
    {
        $actor->getControllerCallback = function () use ($controllerMock) {
            return $controllerMock;
        };
    }
}