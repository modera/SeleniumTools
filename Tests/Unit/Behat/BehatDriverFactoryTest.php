<?php

namespace Modera\Component\SeleniumTools\Tests\Unit\Behat;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Modera\Component\SeleniumTools\Actor;
use Modera\Component\SeleniumTools\Behat\BehatDriverFactory;
use Modera\Component\SeleniumTools\Behat\InvalidConfigException;

class FooBehatDriverFactory extends BehatDriverFactory
{
    /**
     * @var callable
     */
    public $doCreateDriverCallback;

    protected function doCreateDriver(Actor $actor, $driverConfig, $capabilities)
    {
        return call_user_func(
            $this->doCreateDriverCallback, $actor, $driverConfig, $capabilities
        );
    }

}

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class BehatDriverFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateDriver()
    {
        $config = array(
            'drivers' => array(
                'foo_driver' => array(
                    'browser' => 'chrome',
                    'host' => 'foo_host',
                    'connection_timeout' => 'foo_ct',
                    'request_timeout' => 'foo_t'
                )
            ),
            'harnesses' => array(
                'foo_harness' => array(
                    'driver' => 'foo_driver'
                )
            )
        );

        $factory = new FooBehatDriverFactory($config, 'foo_harness');
        $factory->doCreateDriverCallback = function($actor, $driverConfig, $capabilities) {
            return array(
                'actor' => $actor,
                'driver_config' => $driverConfig,
                'capabilities' => $capabilities,
            );
        };

        $actorMock = \Phake::mock(Actor::class);
        $givenArgs = $factory->createDriver($actorMock);

        $this->assertTrue(is_array($givenArgs));
        $this->assertSame($actorMock, $givenArgs['actor']);
        $this->assertSame($config['drivers']['foo_driver'], $givenArgs['driver_config']);
        $this->assertInstanceOf(DesiredCapabilities::class, $givenArgs['capabilities']);
    }

    public function testCreateDriverWhenHarnessNotDefined()
    {
        $factory = new BehatDriverFactory(array(), 'foo_harness');

        try {
            $factory->createDriver(\Phake::mock(Actor::class));
        } catch (InvalidConfigException $e) {
            $this->assertEquals($e->getMessage(), 'Unable to find a harness "foo_harness" in config.');
            $this->assertEquals('/', $e->getPath());

            return;
        }

        $this->fail();
    }

    public function testCreateDriverWhenReferredDriverNotDefined()
    {
        $config = array(
            'harnesses' => array(
                'foo_harness' => array(
                    'driver' => 'boo',
                ),
            ),
        );

        $factory = new BehatDriverFactory($config, 'foo_harness');

        try {
            $factory->createDriver(\Phake::mock(Actor::class));
        } catch (InvalidConfigException $e) {
            $this->assertEquals(
                'Harness "foo_harness" configuration relies on a driver "boo" which is not declared in config.',
                $e->getMessage()
            );
            $this->assertEquals('/harnesses/foo_harness', $e->getPath());

            return;
        }

        $this->fail();
    }

    public function testCreateDriverWithUnknownBrowser()
    {
        $config = array(
            'harnesses' => array(
                'foo_harness' => array(
                    'driver' => 'boo',
                ),
            ),
            'drivers' => array(
                'boo' => array(
                    'browser' => 'wat'
                )
            )
        );

        $factory = new BehatDriverFactory($config, 'foo_harness');

        try {
            $factory->createDriver(\Phake::mock(Actor::class));
        } catch (InvalidConfigException $e) {
            $this->assertEquals(
                'Unknown browser config "wat" is requested. See DesiredCapabilities class for a list of available options.',
                $e->getMessage()
            );

            return;
        }

        $this->fail();
    }
}