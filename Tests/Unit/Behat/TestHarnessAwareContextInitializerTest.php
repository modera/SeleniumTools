<?php

namespace Modera\Component\SeleniumTools\Tests\Unit\Behat;

use Behat\Behat\Context\Context;
use Modera\Component\SeleniumTools\Behat\HarnessAwareContextInterface;
use Modera\Component\SeleniumTools\Behat\TestHarnessAwareContextInitializer;
use Modera\Component\SeleniumTools\Behat\TestHarnessFactory;
use PHPUnit\Framework\TestCase;

class DummyHarnessAwareContext implements Context, HarnessAwareContextInterface
{
    public $invocations = [];

    public function acceptHarnessFactory(TestHarnessFactory $harnessFactory)
    {
        $this->invocations[] = $harnessFactory;
    }
}

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class TestHarnessAwareContextInitializerTest extends TestCase
{
    public function testInitializeContext_properContextInUse()
    {
        $factory = \Phake::mock(TestHarnessFactory::class);
        $context = new DummyHarnessAwareContext();

        $initializer = new TestHarnessAwareContextInitializer($factory);
        $initializer->initializeContext($context);

        $this->assertEquals(1, count($context->invocations));
        $this->assertSame($factory, $context->invocations[0]);
    }
}