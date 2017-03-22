<?php

namespace Modera\Component\SeleniumTools\Tests\Fixtures;

use Modera\Component\SeleniumTools\Actor;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class ActorWithCallbacks extends Actor
{
    public $createDriverInstanceCallback;

    public $getControllerCallback;

    /**
     * {@inheritdoc}
     */
    protected function createDriverInstance($host, $connectionTimeout, $requestTimeout)
    {
        if ($this->createDriverInstanceCallback) {
            return call_user_func_array($this->createDriverInstanceCallback, [$host, $connectionTimeout, $requestTimeout]);
        } else {
            return parent::createDriverInstance($host, $connectionTimeout, $requestTimeout);
        }
    }

    protected function getController()
    {
        if ($this->getControllerCallback) {
            return call_user_func_array($this->getControllerCallback, []);
        } else {
            return parent::getController();
        }
    }
}