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
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Sonatra\Bundle\DoctrineExtensionsBundle\Exception\UnexpectedTypeException;
use Sonatra\Bundle\DoctrineExtensionsBundle\Exception\ConstraintDefinitionException;

/**
 * Unique Entity Validator checks if one or a set of fields contain unique values.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class UniqueEntityValidator extends ConstraintValidator
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

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
     * Validate.
     *
     * @param object     $entity
     * @param Constraint $constraint
     *
     * @throws UnexpectedTypeException
     * @throws ConstraintDefinitionException
     */
    public function validate($entity, Constraint $constraint)
    {
        /* @var UniqueEntity $constraint */

        $em = $this->getObjectManager($entity, $constraint);
        $fields = (array) $constraint->fields;
        $criteria = $this->getCriteria($entity, $constraint, $em);

        if (null === $criteria) {
            return;
        }

        $filters = $this->findFilters($em, (array) $constraint->filters, $constraint->allFilters);

        $this->actionFilter($em, 'disable', $filters);
        $repository = $em->getRepository(get_class($entity));
        $result = $repository->{$constraint->repositoryMethod}($criteria);
        $this->actionFilter($em, 'enable', $filters);

        if (is_array($result)) {
            reset($result);
        }

        /* If no entity matched the query criteria or a single entity matched,
         * which is the same as the entity being validated, the criteria is
         * unique.
         */
        if (0 === count($result) || (1 === count($result) && $entity === ($result instanceof \Iterator ? $result->current() : current($result)))) {
            return;
        }

        $errorPath = null !== $constraint->errorPath ? $constraint->errorPath : $fields[0];

        $this->context->addViolationAt($errorPath, $constraint->message, array(), $criteria[$fields[0]]);
    }

    /**
     * Pre validate entity.
     *
     * @param object     $entity
     * @param Constraint $constraint
     *
     * @return ObjectManager
     *
     * @throws UnexpectedTypeException
     * @throws ConstraintDefinitionException
     */
    private function getObjectManager($entity, Constraint $constraint)
    {
        $this->validateConstraint($constraint);
        /* @var UniqueEntity $constraint */

        return $this->findObjectManager($entity, $constraint);
    }

    /**
     * @param Constraint $constraint
     *
     * @throws UnexpectedTypeException
     * @throws ConstraintDefinitionException
     */
    private function validateConstraint(Constraint $constraint)
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
     * @param object       $entity
     * @param UniqueEntity $constraint
     *
     * @return ObjectManager
     *
     * @throws ConstraintDefinitionException
     */
    private function findObjectManager($entity, UniqueEntity $constraint)
    {
        if ($constraint->em) {
            $em = $this->registry->getManager($constraint->em);

            if (!$em) {
                throw new ConstraintDefinitionException(sprintf('Object manager "%s" does not exist.', $constraint->em));
            }
        } else {
            $em = $this->registry->getManagerForClass(get_class($entity));

            if (!$em) {
                throw new ConstraintDefinitionException(sprintf('Unable to find the object manager associated with an entity of class "%s".', get_class($entity)));
            }
        }

        return $em;
    }

    /**
     * Gets criteria.
     *
     * @param object        $entity
     * @param Constraint    $constraint
     * @param ObjectManager $em
     *
     * @return array|null Null if there is no constraint
     *
     * @throws ConstraintDefinitionException
     */
    private function getCriteria($entity, Constraint $constraint, ObjectManager $em)
    {
        /* @var UniqueEntity $constraint */
        /* @var \Doctrine\ORM\Mapping\ClassMetadata $class */
        $class = $em->getClassMetadata(get_class($entity));
        $fields = (array) $constraint->fields;
        $criteria = array();

        foreach ($fields as $fieldName) {
            if (!$class->hasField($fieldName) && !$class->hasAssociation($fieldName)) {
                throw new ConstraintDefinitionException(sprintf("The field '%s' is not mapped by Doctrine, so it cannot be validated for uniqueness.", $fieldName));
            }

            $criteria[$fieldName] = $class->reflFields[$fieldName]->getValue($entity);

            /* @var UniqueEntity $constraint */
            if ($constraint->ignoreNull && null === $criteria[$fieldName]) {
                $criteria = null;
                break;
            }

            $this->findFieldCriteria($criteria, $em, $class, $fieldName);
        }

        return $criteria;
    }

    /**
     * Finds the criteria for the entity field.
     *
     * @param array         $criteria  By reference
     * @param ObjectManager $em
     * @param ClassMetadata $class
     * @param string        $fieldName
     *
     * @throws ConstraintDefinitionException
     */
    private function findFieldCriteria(array &$criteria, ObjectManager $em, ClassMetadata $class, $fieldName)
    {
        if (null !== $criteria[$fieldName] && $class->hasAssociation($fieldName)) {
            /* Ensure the Proxy is initialized before using reflection to
             * read its identifiers. This is necessary because the wrapped
             * getter methods in the Proxy are being bypassed.
             */
            $em->initializeObject($criteria[$fieldName]);

            $relatedClass = $em->getClassMetadata($class->getAssociationTargetClass($fieldName));
            $relatedId = $relatedClass->getIdentifierValues($criteria[$fieldName]);

            if (count($relatedId) > 1) {
                throw new ConstraintDefinitionException(
                    "Associated entities are not allowed to have more than one identifier field to be " .
                    "part of a unique constraint in: ".$class->getName()."#".$fieldName
                );
            }
            $criteria[$fieldName] = array_pop($relatedId);
        }
    }

    /**
     * Get the list of SQL Filter name must to be disabled.
     *
     * @param ObjectManager $om      The ObjectManager instance
     * @param array         $filters The list of SQL Filter
     * @param bool          $all     Force all SQL Filter
     *
     * @return array
     */
    private function findFilters(ObjectManager $om, array $filters, $all = false)
    {
        if (!$om instanceof EntityManager || (empty($filters) && !$all)) {
            return array();
        }

        $all = ($all && !empty($filters)) ? false : $all;
        $enabledFilters = $om->getFilters()->getEnabledFilters();

        return $this->doFindFilters($filters, $enabledFilters, $all);
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
    private function doFindFilters(array $filters, array $enabledFilters, $all)
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
    private function actionFilter(ObjectManager $om, $action, array $filters)
    {
        if ($om instanceof EntityManager) {
            foreach ($filters as $name) {
                $om->getFilters()->$action($name);
            }
        }
    }
}
