<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\DoctrineExtensionsBundle\Tests\Fixtures;

use Symfony\Component\Validator\ExecutionContext;

/**
 * Fixture class for doctrine callback validator.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class FooCallbackValidatorClass
{
    /**
     * Validates static method in class.
     *
     * @param object           $object
     * @param ExecutionContext $context
     *
     * @return bool
     */
    public static function validateCallback($object, ExecutionContext $context)
    {
        $context->addViolation('Callback message', array('{{ value }}' => 'foobar'), 'invalidValue');

        return false;
    }
}
