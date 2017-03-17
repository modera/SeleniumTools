<?php

namespace Modera\Component\SeleniumTools\Registry;

use Modera\Component\SeleniumTools\Actor;
use Modera\Component\SeleniumTools\PageObjects\MJRBackendPageObject;
use Modera\Component\SeleniumTools\Querying\ExtDeferredQueryHandler;
use Modera\Component\SeleniumTools\RegistryFactoryInterface;
use Modera\Component\SeleniumTools\RegistryInterface;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class MJR implements RegistryInterface, RegistryFactoryInterface
{
    /**
     * @var Actor
     */
    private $actor;

    /**
     * @var MJRBackendPageObject
     */
    private $mjrBackendPageObject;

    /**
     * @var ExtDeferredQueryHandler
     */
    private $extDeferredQueryHandler;

    /**
     * @param Actor $actor
     */
    public function __construct(Actor $actor)
    {
        $this->actor = $actor;
    }

    /**
     * {@inheritdoc}
     */
    public function getActor()
    {
        return $this->actor;
    }

    /**
     * {@inheritdoc}
     */
    public function create(Actor $actor)
    {
        return $this;
    }

    /**
     * @return MJRBackendPageObject
     */
    public function getMJRBackendPageObject()
    {
        if (!$this->mjrBackendPageObject) {
            $this->mjrBackendPageObject = new MJRBackendPageObject($this->actor->getDriver());
        }

        return $this->mjrBackendPageObject;
    }

    /**
     * @return ExtDeferredQueryHandler
     */
    public function getExtDeferredQueryHandler()
    {
        if (!$this->extDeferredQueryHandler) {
            $this->extDeferredQueryHandler = new ExtDeferredQueryHandler($this->actor->getDriver());
        }

        return $this->extDeferredQueryHandler;
    }

    public function page()
    {
        return $this->getMJRBackendPageObject();
    }

    public function q()
    {
        return $this->getExtDeferredQueryHandler();
    }
}