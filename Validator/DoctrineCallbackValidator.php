<?php

/**
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\DoctrineExtensionsBundle\Validator;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Sonatra\Bundle\DoctrineExtensionsBundle\Validator\Exception\UnexpectedTypeException;
use Sonatra\Bundle\DoctrineExtensionsBundle\Validator\Exception\ConstraintDefinitionException;

/**
 * Validator for Callback constraint with Doctrine Entity Manager.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DoctrineCallbackValidator extends ConstraintValidator
{
    protected $registry;

    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $object     The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @throws UnexpectedTypeException       When unexpected type
     * @throws ConstraintDefinitionException When the targeted by Callback constraint is not a valid callable
     * @throws ConstraintDefinitionException When the targeted by Callback constraint does not exist
     */
    public function validate($object, Constraint $constraint)
    {
        /* @var DoctrineCallback $constraint */

        if (null === $object) {
            return;
        }

        $methods = $constraint->methods;

        if (!is_array($methods)) {
            throw new UnexpectedTypeException($methods, 'array');
        }

        $em = $this->registry->getManagerForClass(get_class($object));

        foreach ($methods as $method) {
            if (is_array($method) || $method instanceof \Closure) {
                if (!is_callable($method)) {
                    throw new ConstraintDefinitionException(sprintf('"%s::%s" targeted by Callback constraint is not a valid callable', $method[0], $method[1]));
                }

                call_user_func($method, $object, $this->context, $em);

            } else {
                if (!method_exists($object, $method)) {
                    throw new ConstraintDefinitionException(sprintf('Method "%s" targeted by Callback constraint does not exist', $method));
                }

                $object->$method($this->context, $em);
            }
        }
    }
}
