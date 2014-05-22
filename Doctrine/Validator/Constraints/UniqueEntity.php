<?php

/**
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\DoctrineExtensionsBundle\Doctrine\Validator\Constraints;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity as BaseUniqueEntity;

/**
 * Constraint for the Unique Entity validator with disable sql filter option.
 *
 * @Annotation
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class UniqueEntity extends BaseUniqueEntity
{
    public $service = 'sonatra.doctrine_extensions.orm.validator.unique';
    public $filters = array();
    public $allFilters = true;
}
