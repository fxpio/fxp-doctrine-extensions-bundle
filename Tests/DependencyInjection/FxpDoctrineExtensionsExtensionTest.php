<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Bundle\DoctrineExtensionsBundle\Tests\DependencyInjection;

use Fxp\Bundle\DoctrineExtensionsBundle\DependencyInjection\FxpDoctrineExtensionsExtension;
use Fxp\Bundle\DoctrineExtensionsBundle\FxpDoctrineExtensionsBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests case for Extension.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class FxpDoctrineExtensionsExtensionTest extends TestCase
{
    public function testExtensionExist(): void
    {
        $container = $this->createContainer();

        $this->assertTrue($container->hasExtension('fxp_doctrine_extensions'));
        $this->assertTrue($container->hasDefinition('fxp.doctrine_extensions.orm.validator.unique'));
        $this->assertTrue($container->hasDefinition('fxp_doctrine_extensions.orm.validator.doctrine_callback'));
    }

    /**
     * @throws
     *
     * @return ContainerBuilder
     */
    protected function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $bundle = new FxpDoctrineExtensionsBundle();
        $bundle->build($container);

        $extension = new FxpDoctrineExtensionsExtension();
        $container->registerExtension($extension);
        $extension->load([], $container);

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        return $container;
    }
}
