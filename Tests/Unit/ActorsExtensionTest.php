<?php

namespace Modera\Component\SeleniumTools\Tests\Unit;

use Modera\Component\SeleniumTools\Behat\ActorsExtension;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class ActorsExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $container = new ContainerBuilder();
        $config = array();

        $_SERVER['BEHAT_FOO'] = 'foo-value';
        $_SERVER['BAR'] = 'bar-value';

        $ext = new ActorsExtension();
        $ext->load($container, $config);

        $this->assertTrue($container->hasDefinition('actors.harness_factory'));
        $this->assertTrue($container->hasDefinition('actors.context_initializer'));

        $this->assertTrue($container->hasParameter('BEHAT_FOO'));
        $this->assertEquals('foo-value', $container->getParameter('BEHAT_FOO'));
        $this->assertFalse($container->hasParameter('BAR'));

        unset($_SERVER['BEHAT_FOO']);
        unset($_SERVER['BAR']);
    }
}