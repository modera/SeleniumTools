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
     * @var array
     */
    private $context = array();

    /**
     * Capabilities that are shared by all associated actors if it is not overridden per-actor.
     *
     * @var array
     */
    private $capabilities;

    /**
     * @var callable
     */
    private $additionalActorArgumentsFactory;

    /**
     * @param string $name
     * @param array $capabilities
     * @param callable $additionalActorArgumentsFactory
     */
    public function __construct($name, array $capabilities, callable $additionalActorArgumentsFactory = null)
    {
        $this->name = $name;
        $this->capabilities = $capabilities;
        $this->additionalActorArgumentsFactory = $additionalActorArgumentsFactory;
    }

    /**
     * @param string $actorName
     * @param string $startUrl
     * @param array $capabilities
     *
     * @return TestHarness
     */
    public function addActor($actorName, $startUrl, array $capabilities = array())
    {
        if (count($capabilities) == 0) {
            $capabilities = $this->capabilities;
        }

        $this->actors[$actorName] = new Actor(
            $actorName, $startUrl, $capabilities, $this, $this->additionalActorArgumentsFactory
        );

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