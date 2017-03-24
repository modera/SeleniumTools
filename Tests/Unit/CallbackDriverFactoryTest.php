<?php

namespace Modera\Component\SeleniumTools\Tests\Unit;

use Modera\Component\SeleniumTools\Actor;
use Modera\Component\SeleniumTools\CallbackDriverFactory;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class CallbackDriverFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateDriver()
    {
        $factory = new CallbackDriverFactory(function(Actor $actor, $driver) {
            return array(
                'actor' => $actor,
                'driver' => $driver,
            );
        });

        $actor = \Phake::mock(Actor::class);
        $result = $factory->createDriver($actor);

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('actor', $result);
        $this->assertSame($actor, $result['actor']);
        $this->assertArrayHasKey('driver', $result);
        $this->assertSame($factory, $result['driver']);
    }
}