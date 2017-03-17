<?php

namespace Modera\Component\SeleniumTools;

use Modera\Component\SeleniumTools\Exceptions\NoSuchActorException;
use Selenium\Client;

/**
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
     * Behaviours that are shared by all associated actors if it is not overridden per-actor.
     *
     * @var array
     */
    private $behaviours;

    /**
     * @var callable
     */
    private $additionalActorArgumentsFactory;

    /**
     * An actor that is pefroming actions as of now.
     *
     * @var Actor
     */
    private $activeActor;

    /**
     * @param string $name
     * @param array $capabilities
     * @param callable $additionalActorArgumentsFactory
     */
    public function __construct($name, array $capabilities, callable $additionalActorArgumentsFactory = null)
    {
        $this->name = $name;
        $this->behaviours = $capabilities;
        $this->additionalActorArgumentsFactory = $additionalActorArgumentsFactory;
    }

    /**
     * @param string $actorName
     * @param string $startUrl
     * @param array $behaviours  See Actor::BHR_* constants
     *
     * @return TestHarness
     */
    public function addActor($actorName, $startUrl, array $behaviours = array())
    {
        if (count($behaviours) == 0) {
            $behaviours = $this->behaviours;
        }

        $this->actors[$actorName] = new Actor(
            $actorName, $startUrl, $behaviours, $this, $this->additionalActorArgumentsFactory
        );

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
     * @iternal
     *
     * @param Actor $actor
     *
     * @return bool
     */
    public function isActorActive(Actor $actor)
    {
        return $this->activeActor === $actor;
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