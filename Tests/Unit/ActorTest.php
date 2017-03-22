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
        $passedArguments = [];

        $actor = new ActorWithCallbacks('bob', 'http://example.com', $this->mockHarness);
        $actor->createDriverInstanceCallback = function() use(&$passedArguments, $driverWannaBe) {
            $passedArguments = func_get_args();

            return $driverWannaBe;
        };

        $this->assertFalse($actor->isDriverCreated());

        $driver = $actor->getDriver();
        $anotherDriver = $actor->getDriver();

        $this->assertSame($driverWannaBe, $driver);
        $this->assertSame($driver, $anotherDriver, 'Driver must have been runtime cached.');

        $this->assertEquals('http://localhost:4444/wd/hub', $passedArguments[0]);
        $this->assertEquals(30 * 1000, $passedArguments[1]);
        $this->assertEquals(15 * 10000, $passedArguments[2]);

        $this->assertTrue($actor->isDriverCreated());
    }

    /**
     * @expectedExceptionMessage Actor "bob" was unable to establish connection with Selenium: "Foobar".
     * @expectedException \Modera\Component\SeleniumTools\Exceptions\ActorExecutionException
     */
    public function testCreateDriverWithException()
    {
        $actor = new ActorWithCallbacks('bob', 'http://example.com', $this->mockHarness);
        $actor->createDriverInstanceCallback = function() {
            throw new \Exception('Foobar');
        };

        $actor->getDriver();

        $this->fail('Exception must have been thrown');
    }

    public function testCreateDriverUsingEnvVariables()
    {
        $_SERVER['SELENIUM_HOST'] = 'foo-host';
        $_SERVER['SELENIUM_CONNECTION_TIMEOUT'] = 'foo-timeout';
        $_SERVER['SELENIUM_REQUEST_TIMEOUT'] = 'foo-request-timeout';

        $passedArguments = [];

        $actor = new ActorWithCallbacks('bob', 'http://example.com', $this->mockHarness);
        $actor->createDriverInstanceCallback = function() use(&$passedArguments) {
            $passedArguments = func_get_args();
        };

        $actor->getDriver();

        $this->assertEquals($_SERVER['SELENIUM_HOST'], $passedArguments[0]);
        $this->assertEquals($_SERVER['SELENIUM_CONNECTION_TIMEOUT'], $passedArguments[1]);
        $this->assertEquals($_SERVER['SELENIUM_REQUEST_TIMEOUT'], $passedArguments[2]);

        unset($_SERVER['SELENIUM_HOST']);
        unset($_SERVER['SELENIUM_CONNECTION_TIMEOUT']);
        unset($_SERVER['SELENIUM_REQUEST_TIMEOUT']);
    }

    public function testRunningInActor()
    {
        $driverWannaBe = new \stdClass();
        $controllerMock = \Phake::mock(ActorBrowserController::class);

        $additionalArg1 = new \stdClass();
        $additionalArg2 = new \stdClass();

        $actor = new ActorWithCallbacks(
            'bob',
            'http://example.com',
            $this->mockHarness,
            [],
            function() use($additionalArg1, $additionalArg2) {
                return [$additionalArg1, $additionalArg2];
            }
        );

        $this->mockDriverAndControllerMethods($actor, $controllerMock, $driverWannaBe);
        $this->trainControllerToLaunchBrowserAndCreateDriver($controllerMock, $actor);

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
        $this->assertSame($driverWannaBe, $invocationArgs[0]['driver']);
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
        $driverWannaBe = new \stdClass();
        $controllerMock = \Phake::mock(ActorBrowserController::class);

        $actor = new ActorWithCallbacks(
            'bob',
            'http://example.com',
            $this->mockHarness
        );

        $this->mockDriverAndControllerMethods($actor, $controllerMock, $driverWannaBe);
        $this->trainControllerToLaunchBrowserAndCreateDriver($controllerMock, $actor);

        $runCallback = function() {
            throw new \RuntimeException('Foobar');
        };

        $actor->run($runCallback);
    }

    public function testRunningWithMaximizedWindow()
    {
        $driverWannaBe = new \stdClass();
        $controllerMock = \Phake::mock(ActorBrowserController::class);

        $actor = new ActorWithCallbacks(
            'bob',
            'http://example.com',
            $this->mockHarness
        );

        $this->mockDriverAndControllerMethods($actor, $controllerMock, $driverWannaBe);
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

        $this->mockDriverAndControllerMethods($actor, $controllerMock, $driverMock);
        $this->trainControllerToLaunchBrowserAndCreateDriver($controllerMock, $actor);

        \Phake::when($controllerMock)
            ->isFocusingNeeded()
            ->thenReturn(true)
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

        $this->trainControllerToLaunchBrowserAndCreateDriver($controllerMock, $actor);
        $this->mockDriverAndControllerMethods($actor, $controllerMock, $driverMock);

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

    private function mockDriverAndControllerMethods($actor, $controllerMock, $driverWannaBe)
    {
        $actor->createDriverInstanceCallback = function () use ($driverWannaBe) {
            return $driverWannaBe;
        };
        $actor->getControllerCallback = function () use ($controllerMock) {
            return $controllerMock;
        };
    }
}