<?php

/**
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\DoctrineExtensionsBundle\Tests\Fixtures;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Validator\ExecutionContext;

/**
 * Fixture object for doctrine callback validator.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class FooCallbackValidatorObject
{
    /**
     * Validates method in object instance.
     *
     * @param ExecutionContext $context
     * @param ManagerRegistry  $registry
     *
     * @return bool
     */
    public function validate(ExecutionContext $context, ManagerRegistry $registry)
    {
        $context->addViolation('My message', array('{{ value }}' => 'foobar'), 'invalidValue');

        return false;
    }

    /**
     * Validates static method in object instance.
     *
     * @param $object
     * @param ExecutionContext $context
     * @param ManagerRegistry  $registry
     *
     * @return bool
     */
    public static function validateStatic($object, ExecutionContext $context, ManagerRegistry $registry)
    {
        $context->addViolation('Static message', array('{{ value }}' => 'baz'), 'otherInvalidValue');

        return false;
    }
}
