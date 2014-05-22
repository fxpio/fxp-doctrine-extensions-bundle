<?php

/**
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\DoctrineExtensionsBundle\Exception;

use Symfony\Component\Validator\Exception\ConstraintDefinitionException as BaseConstraintDefinitionException;

/**
 * Base ConstraintDefinitionException for the doctrine extensions component.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ConstraintDefinitionException extends BaseConstraintDefinitionException implements ExceptionInterface
{
}
