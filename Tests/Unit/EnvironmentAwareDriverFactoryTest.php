<?php

namespace Modera\Component\SeleniumTools\Tests\Unit;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Modera\Component\SeleniumTools\Actor;
use Modera\Component\SeleniumTools\EnvironmentAwareDriverFactory;

class FooEnvironmentAwareDriverFactory extends EnvironmentAwareDriverFactory
{
    public $doCreateDriverCallback;

    protected function doCreateDriver(array $config, $capabilities)
    {
        return call_user_func($this->doCreateDriverCallback, $config, $capabilities);
    }
}

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class EnvironmentAwareDriverFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateDriver()
    {
        $invocation = array();

        $factory = new FooEnvironmentAwareDriverFactory();
        $factory->doCreateDriverCallback = function(array $config, $capabilities) use(&$invocation) {
            $invocation = array(
                'config' => $config,
                'capabilities' => $capabilities,
            );

            return 'foo-driver';
        };

        $driver = $factory->createDriver(\Phake::mock(Actor::class));

        $this->assertEquals('foo-driver', $driver);
        $this->assertArrayHasKey('config', $invocation);
        $this->assertArrayHasKey('host', $invocation['config']);
        $config = $invocation['config'];
        $this->assertEquals('http://localhost:4444/wd/hub', $config['host']);
        $this->assertEquals(30000, $config['connection_timeout']);
        $this->assertArrayHasKey('request_timeout', $config);
        $this->assertEquals(150000, $config['request_timeout']);
        $this->assertArrayHasKey('browser', $config);
        $this->assertEquals('chrome', $config['browser']);
        $this->assertArrayHasKey('capabilities', $invocation);
        $this->assertInstanceOf(DesiredCapabilities::class, $invocation['capabilities']);
    }
}