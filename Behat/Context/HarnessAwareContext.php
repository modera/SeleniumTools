<?php

namespace Modera\Component\SeleniumTools\Behat\Context;

use Behat\Behat\Context\Context;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Modera\Component\SeleniumTools\Actor;
use Modera\Component\SeleniumTools\Behat\HarnessAwareContextInterface;
use Modera\Component\SeleniumTools\Behat\TestHarnessFactory;
use Modera\Component\SeleniumTools\PageObjects\MJRBackendPageObject;
use Modera\Component\SeleniumTools\Querying\ExtDeferredQueryHandler;
use Modera\Component\SeleniumTools\TestHarness;

/**
 * Provides baseline integration with TestHarness, allows to run scenarios which involve several actors (the class
 * still can be used when you need to run single-actor scenario as well).
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class HarnessAwareContext implements Context, HarnessAwareContextInterface
{
    /**
     * @var TestHarness
     */
    private $harness;

    /**
     * Marked as static because active actor state needs to be shared between Context (because an actor
     * can become active in one context, but later some other functions will rely on it from other
     * context).
     *
     * @see \Modera\Component\SeleniumTools\Behat\Context\MJRContext::sessionIsSwitchedTo
     *
     * @var Actor
     */
    private static $activeActor;

    /**
     * @var TestHarnessFactory
     */
    private $harnessFactory;

    /**
     * {@inheritdoc}
     */
    public function acceptHarnessFactory(TestHarnessFactory $harnessFactory)
    {
        $this->harnessFactory = $harnessFactory;
    }

    /**
     * @internal
     * @BeforeScenario
     */
    public function onBeforeScenario()
    {
        if (!$this->harness) {
            // See acceptHarnessFactory()
            $this->harness = $this->harnessFactory->createHarness('default');
        }

        if (!$this->harness) {
            throw new \RuntimeException('No harness has been created by HarnessFactory.');
        }
    }

    /**
     * @internal
     * @AfterScenario
     */
    public function onAfterScenario()
    {
        $this->harness->halt();
        $this->harness = null;
    }

    /**
     * @param string $name
     *
     * @return Actor
     */
    protected function getActor($name = null)
    {
        if (null !== $name) {
            $this->switchActor($name);
        }

        if (null === self::$activeActor) {
            throw new \DomainException('There is no active actor set yet.');
        }

        return self::$activeActor;
    }

    /**
     * @param string $name
     */
    protected function switchActor($name)
    {
        self::$activeActor = $this->harness->getActor($name);
    }

    /**
     * @return Actor
     */
    protected function getActiveActor()
    {
        if (!self::$activeActor) {
            throw new \DomainException("No actor activated yet.");
        }

        return self::$activeActor;
    }

    /**
     * @param callable $callback
     */
    protected function runActiveActor(callable $callback)
    {
        $this->getActiveActor()->run(function(RemoteWebDriver $admin, Actor $actor) use($callback) {
            $callback($admin, $actor, new MJRBackendPageObject($admin), new ExtDeferredQueryHandler($admin));
        });
    }
}