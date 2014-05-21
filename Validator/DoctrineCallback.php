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

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DoctrineCallback extends Constraint
{
    public $methods;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'validator.doctrinecallback';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return array('methods');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'methods';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
