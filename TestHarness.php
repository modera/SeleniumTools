<?php

namespace Modera\Component\SeleniumTools;

use Modera\Component\SeleniumTools\Exceptions\NoSuchActorException;

/**
 * A component that is responsible for orchestrating multi-actors testing sessions.
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class TestHarness
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Actor[]
     */
    private $actors = array();

    /**
     * Can be used to share data between actors.
     *
     * @var array
     */
    private $context = array();

    /**
     * @var callable
     */
    private $additionalActorArgumentsFactory;

    /**
     * An actor that is performing actions as of now.
     *
     * @var Actor
     */
    private $activeActor;

    /**
     * @var callable
     */
    private $driverFactory;

    /**
     * @param string $name
     * @param callable $additionalActorArgumentsFactory
     * @param DriverFactoryInterface $driverFactory
     */
    public function __construct(
        $name,
        callable $additionalActorArgumentsFactory = null,
        DriverFactoryInterface $driverFactory = null
    )
    {
        $this->name = $name;
        $this->additionalActorArgumentsFactory = $additionalActorArgumentsFactory;
        $this->driverFactory = $driverFactory;
    }

    /**
     * @return callable
     */
    public function getAdditionalActorArgumentsFactory()
    {
        return $this->additionalActorArgumentsFactory;
    }

    /**
     * @param string $actorName
     * @param string $startUrl
     *
     * @return TestHarness
     */
    public function addActor($actorName, $startUrl)
    {
        $this->actors[$actorName] = new Actor($actorName, $startUrl, $this);

        return $this;
    }

    /**
     * @param string $actorName
     * @param Actor $actor
     *
     * @return TestHarness
     */
    public function addActorInstance($actorName, Actor $actor)
    {
        $this->actors[$actorName] = $actor;

        return $this;
    }

    /**
     * @param string $actorName
     *
     * @return bool
     */
    public function hasActor($actorName)
    {
        return isset($this->actors[$actorName]);
    }

    /**
     * @throws NoSuchActorException
     *
     * @param string $actorName
     *
     * @return Actor
     */
    public function getActor($actorName)
    {
        if (!isset($this->actors[$actorName])) {
            throw new NoSuchActorException(
                sprintf('Actor "%s" has not been defined yet. Have you used "addActor" method to add it ?', $actorName)
            );
        }

        return $this->actors[$actorName];
    }

    /**
     * @param string $actorName
     * @param callable $callback
     *
     * @return TestHarness
     */
    public function runAs($actorName, callable $callback)
    {
        $this->getActor($actorName)->run($callback);

        return $this;
    }

    public function halt()
    {
        foreach ($this->actors as $actor) {
            $actor->kill();
        }

        $this->context = array();
    }

    /**
     * @return Actor
     */
    public function getActiveActor()
    {
        return $this->activeActor;
    }

    /**
     * @internal
     *
     * @param Actor $activeActor
     */
    public function setActiveActor(Actor $activeActor)
    {
        $this->activeActor = $activeActor;
    }

    /**
     * @internal
     *
     * @param Actor $actor
     *
     * @return bool
     */
    public function isActorActive(Actor $actor)
    {
        return $this->activeActor === $actor;
    }

    /**
     * @return DriverFactoryInterface
     */
    public function getDriverFactory()
    {
        if (!$this->driverFactory) {
            $this->driverFactory = new EnvironmentAwareDriverFactory();
        }

        return $this->driverFactory;
    }

    /**
     * @internal
     *
     * @param Actor $actor
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    public function createDriver(Actor $actor)
    {
        return $this->getDriverFactory()->createDriver($actor);
    }

    // context:

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setContextValue($key, $value)
    {
        $this->context[$key] = $value;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getContextValue($key)
    {
        if (!$this->hasContextValue($key)) {
            throw new \RuntimeException(sprintf('Context has no value for key "%s".', $key));
        }

        return $this->context[$key];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasContextValue($key)
    {
        return isset($this->context[$key]);
    }
}