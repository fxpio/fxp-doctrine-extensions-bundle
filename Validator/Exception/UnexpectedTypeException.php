<?php

/**
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\DoctrineExtensionsBundle\Validator\Exception;

use Sonatra\Bundle\DoctrineExtensionsBundle\Exception\ExceptionInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException as BaseUnexpectedTypeException;

/**
 * Base UnexpectedTypeException for the doctrine extensions component.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class UnexpectedTypeException extends BaseUnexpectedTypeException implements ExceptionInterface
{
}
