<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\DoctrineExtensionsBundle\Tests\DependencyInjection;

use Sonatra\Bundle\DoctrineExtensionsBundle\DependencyInjection\SonatraDoctrineExtensionsExtension;
use Sonatra\Bundle\DoctrineExtensionsBundle\SonatraDoctrineExtensionsBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests case for Extension.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class SonatraDoctrineExtensionsExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testExtensionExist()
    {
        $container = $this->createContainer();

        $this->assertTrue($container->hasExtension('sonatra_doctrine_extensions'));
        $this->assertTrue($container->hasDefinition('sonatra.doctrine_extensions.orm.validator.unique'));
        $this->assertTrue($container->hasDefinition('sonatra_doctrine_extensions.orm.validator.doctrine_callback'));
    }

    protected function createContainer()
    {
        $container = new ContainerBuilder();

        $bundle = new SonatraDoctrineExtensionsBundle();
        $bundle->build($container);

        $extension = new SonatraDoctrineExtensionsExtension();
        $container->registerExtension($extension);
        $extension->load(array(), $container);

        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        return $container;
    }
}
