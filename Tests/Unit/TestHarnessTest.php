<?php

namespace Modera\Component\SeleniumTools\Tests\Unit;

use Modera\Component\SeleniumTools\Actor;
use Modera\Component\SeleniumTools\TestHarness;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class TestHarnessTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestHarness
     */
    private $th;

    public function setUp()
    {
        $this->th = new TestHarness('foo');
    }

    public function testManagingActors()
    {
        $this->assertFalse($this->th->hasActor('bob'));

        $this->assertSame($this->th, $this->th->addActor('bob', 'http://example.com'));

        $this->assertTrue($this->th->hasActor('bob'));

        $actor = $this->th->getActor('bob');
        $this->assertEquals('bob', $actor->getName());
        $this->assertEquals('http://example.com', $actor->getStartUrl());
    }

    public function testRunningInActor()
    {
        $actor = \Phake::mock(Actor::class);

        $this->th->addActorInstance('bob', $actor);

        $cb = function() {};
        $this->assertSame($this->th, $this->th->runAs('bob', $cb));

        \Phake::verify($actor)
            ->run($cb)
        ;
    }

    public function testWorkingWithActiveActor()
    {
        $this->assertNull($this->th->getActiveActor());

        $actor = \Phake::mock(Actor::class);
        $this->th->setActiveActor($actor);
        $this->assertSame($actor, $this->th->getActiveActor());
    }

    public function testWorkingWithContext()
    {
        $this->assertFalse($this->th->hasContextValue('foo_key'));
        $this->th->setContextValue('foo_key', 'foo_val');
        $this->assertEquals('foo_val', $this->th->getContextValue('foo_key'));
        $this->assertTrue($this->th->hasContextValue('foo_key'));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Context has no value for key "yada".
     */
    public function testGettingUndefinedContextValue()
    {
        $this->th->getContextValue('yada');
    }

    public function testHalting()
    {
        $john = \Phake::mock(Actor::class);

        $this->th->addActorInstance('john', $john);
        $this->th->setContextValue('foo_key', 'foo_value');

        $this->th->halt();

        \Phake::verify($john)
            ->kill()
        ;
        $this->assertFalse($this->th->hasContextValue('foo_key'));
    }
}