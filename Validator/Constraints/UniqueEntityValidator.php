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
use Sonatra\Bundle\DoctrineExtensionsBundle\Util\SqlFilterUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Sonatra\Bundle\DoctrineExtensionsBundle\Exception\UnexpectedTypeException;
use Sonatra\Bundle\DoctrineExtensionsBundle\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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

        $em = Util::getObjectManager($this->registry, $entity, $constraint);
        $fields = (array) $constraint->fields;
        $criteria = $this->getCriteria($entity, $constraint, $em);

        if (null === $criteria) {
            return;
        }

        $result = $this->getResult($entity, $constraint, $criteria, $em);

        if (!$this->isValidResult($result, $entity)) {
            $errorPath = null !== $constraint->errorPath ? $constraint->errorPath : $fields[0];
            $invalidValue = isset($criteria[$errorPath]) ? $criteria[$errorPath] : $criteria[$fields[0]];

            if ($this->context instanceof ExecutionContextInterface) {
                $this->context->buildViolation($constraint->message)
                    ->atPath($errorPath)
                    ->setInvalidValue($invalidValue)
                    ->addViolation();
            } else {
                $this->buildViolation($constraint->message)
                    ->atPath($errorPath)
                    ->setInvalidValue($invalidValue)
                    ->addViolation();
            }
        }
    }

    /**
     * Get entity result.
     *
     * @param object        $entity
     * @param Constraint    $constraint
     * @param array         $criteria
     * @param ObjectManager $em
     *
     * @return array
     */
    private function getResult($entity, Constraint $constraint, array $criteria, ObjectManager $em)
    {
        /* @var UniqueEntity $constraint */
        $filters = SqlFilterUtil::findFilters($em, (array) $constraint->filters, $constraint->allFilters);

        SqlFilterUtil::disableFilters($em, $filters);
        $repository = $em->getRepository(get_class($entity));
        $result = $repository->{$constraint->repositoryMethod}($criteria);
        SqlFilterUtil::enableFilters($em, $filters);

        if (is_array($result)) {
            reset($result);
        }

        return $result;
    }

    /**
     * Check if the result is valid.
     *
     * If no entity matched the query criteria or a single entity matched,
     * which is the same as the entity being validated, the criteria is
     * unique.
     *
     * @param array|\Iterator $result
     * @param object          $entity
     *
     * @return bool
     */
    private function isValidResult($result, $entity)
    {
        return 0 === count($result)
            || (1 === count($result)
                && $entity === ($result instanceof \Iterator
                    ? $result->current()
                    : current($result)));
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
    protected function getCriteria($entity, Constraint $constraint, ObjectManager $em)
    {
        /* @var UniqueEntity $constraint */
        /* @var \Doctrine\ORM\Mapping\ClassMetadata $class */
        $class = $em->getClassMetadata(get_class($entity));
        $fields = (array) $constraint->fields;
        $criteria = array();

        foreach ($fields as $fieldName) {
            $criteria = $this->findFieldCriteria($criteria, $constraint, $em, $class, $entity, $fieldName);

            if (null === $criteria) {
                break;
            }
        }

        return $criteria;
    }

    /**
     * @param array         $criteria
     * @param Constraint    $constraint
     * @param ObjectManager $em
     * @param ClassMetadata $class
     * @param object        $entity
     * @param string        $fieldName
     *
     * @return array|null The new criteria
     *
     * @throws ConstraintDefinitionException
     */
    private function findFieldCriteria(array $criteria, Constraint $constraint, ObjectManager $em, ClassMetadata $class, $entity, $fieldName)
    {
        $this->validateFieldCriteria($class, $fieldName);

        /* @var \Doctrine\ORM\Mapping\ClassMetadata $class */
        $criteria[$fieldName] = $class->reflFields[$fieldName]->getValue($entity);

        /* @var UniqueEntity $constraint */
        if ($constraint->ignoreNull && null === $criteria[$fieldName]) {
            $criteria = null;
        } else {
            $this->findFieldCriteriaStep2($criteria, $em, $class, $fieldName);
        }

        return $criteria;
    }

    /**
     * @param ClassMetadata $class
     * @param string        $fieldName
     *
     * @throws ConstraintDefinitionException
     */
    private function validateFieldCriteria(ClassMetadata $class, $fieldName)
    {
        if (!$class->hasField($fieldName) && !$class->hasAssociation($fieldName)) {
            throw new ConstraintDefinitionException(sprintf("The field '%s' is not mapped by Doctrine, so it cannot be validated for uniqueness.", $fieldName));
        }
    }

    /**
     * Finds the criteria for the entity field.
     *
     * @param array         $criteria
     * @param ObjectManager $em
     * @param ClassMetadata $class
     * @param string        $fieldName
     *
     * @throws ConstraintDefinitionException
     */
    private function findFieldCriteriaStep2(array &$criteria, ObjectManager $em, ClassMetadata $class, $fieldName)
    {
        if (null !== $criteria[$fieldName] && $class->hasAssociation($fieldName)) {
            /* Ensure the Proxy is initialized before using reflection to
             * read its identifiers. This is necessary because the wrapped
             * getter methods in the Proxy are being bypassed.
             */
            $em->initializeObject($criteria[$fieldName]);

            $relatedClass = $em->getClassMetadata($class->getAssociationTargetClass($fieldName));
            $isObject = is_object($criteria[$fieldName]);
            $relatedId = $relatedClass->getIdentifierValues($criteria[$fieldName]);

            if (count($relatedId) > 1) {
                throw new ConstraintDefinitionException(
                    'Associated entities are not allowed to have more than one identifier field to be '.
                    'part of a unique constraint in: '.$class->getName().'#'.$fieldName
                );
            }

            $value = array_pop($relatedId);
            $criteria[$fieldName] = $isObject && null === $value
                ? $this->formatEmptyIdentifier($relatedClass)
                : $value;
        }
    }

    /**
     * Format the empty identifier value for entity with relation.
     *
     * @param ClassMetadata $meta The class metadata of entity relation
     *
     * @return int|string
     */
    private function formatEmptyIdentifier(ClassMetadata $meta)
    {
        $type = $meta->getTypeOfField(current($meta->getIdentifier()));

        switch ($type) {
            case 'bigint':
            case 'decimal':
            case 'integer':
            case 'smallint':
            case 'float':
                return 0;
            case 'guid':
                return '00000000-0000-0000-0000-000000000000';
            default:
                return '';
        }
    }
}
