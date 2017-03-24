<?php

namespace Modera\Component\SeleniumTools;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class CallbackDriverFactory implements DriverFactoryInterface
{
    /**
     * @var callable
     */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver(Actor $actor)
    {
        return call_user_func($this->callback, $actor, $this);
    }
}