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

use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
     * @param ExecutionContextInterface $context
     *
     * @return bool
     */
    public function validate(ExecutionContextInterface $context)
    {
        $context->addViolation('My message', array('{{ value }}' => 'foobar'), 'invalidValue');

        return false;
    }

    /**
     * Validates static method in object instance.
     *
     * @param $object
     * @param ExecutionContextInterface $context
     *
     * @return bool
     */
    public static function validateStatic($object, ExecutionContextInterface $context)
    {
        $context->addViolation('Static message', array('{{ value }}' => 'baz'), 'otherInvalidValue');

        return false;
    }
}
