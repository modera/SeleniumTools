<?php

namespace Modera\Component\SeleniumTools\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;

/**
 * @internal
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class TestHarnessAwareContextInitializer implements ContextInitializer
{
    /**
     * @var TestHarnessFactory
     */
    private $testHarnessFactory;

    /**
     * @param TestHarnessFactory $testHarnessFactory
     */
    public function __construct(TestHarnessFactory $testHarnessFactory)
    {
        $this->testHarnessFactory = $testHarnessFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof HarnessAwareContextInterface) {
            $context->acceptHarnessFactory($this->testHarnessFactory);
        }
    }
}