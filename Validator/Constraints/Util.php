<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\DoctrineExtensionsBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Sonatra\Bundle\DoctrineExtensionsBundle\Exception\ConstraintDefinitionException;
use Sonatra\Bundle\DoctrineExtensionsBundle\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class Util
{
    /**
     * Get the list of SQL Filter name must to be disabled.
     *
     * @param ObjectManager $om      The ObjectManager instance
     * @param array         $filters The list of SQL Filter
     * @param bool          $all     Force all SQL Filter
     *
     * @return array
     */
    public static function findFilters(ObjectManager $om, array $filters, $all = false)
    {
        if (!$om instanceof EntityManager || (empty($filters) && !$all)) {
            return array();
        }

        $all = ($all && !empty($filters)) ? false : $all;
        $enabledFilters = $om->getFilters()->getEnabledFilters();

        return self::doFindFilters($filters, $enabledFilters, $all);
    }

    /**
     * Do find filters.
     *
     * @param array $filters
     * @param array $enabledFilters
     * @param bool  $all
     *
     * @return array
     */
    public static function doFindFilters(array $filters, array $enabledFilters, $all)
    {
        $reactivateFilters = array();

        foreach ($enabledFilters as $name => $filter) {
            if (in_array($name, $filters) || $all) {
                $reactivateFilters[] = $name;
            }
        }

        return $reactivateFilters;
    }

    /**
     * Disable/Enable the SQL Filters.
     *
     * @param ObjectManager $om      The ObjectManager instance
     * @param string        $action  Value : disable|enable
     * @param array         $filters The list of SQL Filter
     */
    public static function actionFilter(ObjectManager $om, $action, array $filters)
    {
        if ($om instanceof EntityManager) {
            foreach ($filters as $name) {
                $om->getFilters()->$action($name);
            }
        }
    }

    /**
     * Pre validate entity.
     *
     * @param ManagerRegistry $registry
     * @param object          $entity
     * @param Constraint      $constraint
     *
     * @return ObjectManager
     *
     * @throws UnexpectedTypeException
     * @throws ConstraintDefinitionException
     */
    public static function getObjectManager(ManagerRegistry $registry, $entity, Constraint $constraint)
    {
        self::validateConstraint($constraint);
        /* @var UniqueEntity $constraint */

        return self::findObjectManager($registry, $entity, $constraint);
    }

    /**
     * @param Constraint $constraint
     *
     * @throws UnexpectedTypeException
     * @throws ConstraintDefinitionException
     */
    private static function validateConstraint(Constraint $constraint)
    {
        if (!$constraint instanceof UniqueEntity) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\UniqueEntity');
        }

        if (!is_array($constraint->fields) && !is_string($constraint->fields)) {
            throw new UnexpectedTypeException($constraint->fields, 'array');
        }

        if (null !== $constraint->errorPath && !is_string($constraint->errorPath)) {
            throw new UnexpectedTypeException($constraint->errorPath, 'string or null');
        }

        if (0 === count((array) $constraint->fields)) {
            throw new ConstraintDefinitionException('At least one field has to be specified.');
        }
    }

    /**
     * @param ManagerRegistry $registry
     * @param object          $entity
     * @param UniqueEntity    $constraint
     *
     * @return ObjectManager
     *
     * @throws ConstraintDefinitionException
     */
    private static function findObjectManager(ManagerRegistry $registry, $entity, UniqueEntity $constraint)
    {
        if ($constraint->em) {
            $em = $registry->getManager($constraint->em);

            if (!$em) {
                throw new ConstraintDefinitionException(sprintf('Object manager "%s" does not exist.', $constraint->em));
            }
        } else {
            $em = $registry->getManagerForClass(get_class($entity));

            if (!$em) {
                throw new ConstraintDefinitionException(sprintf('Unable to find the object manager associated with an entity of class "%s".', get_class($entity)));
            }
        }

        return $em;
    }
}
