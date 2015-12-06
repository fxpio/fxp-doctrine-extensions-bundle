<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\DoctrineExtensionsBundle\Tests\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bridge\Doctrine\Tests\Fixtures\CompositeIntIdEntity;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Tests\Fixtures\FakeMetadataFactory;
use Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdEntity;
use Symfony\Bridge\Doctrine\Tests\Fixtures\DoubleNameEntity;
use Symfony\Bridge\Doctrine\Tests\Fixtures\AssociationEntity;
use Sonatra\Bundle\DoctrineExtensionsBundle\Validator\Constraints\UniqueEntity;
use Sonatra\Bundle\DoctrineExtensionsBundle\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Validator\Validator\RecursiveValidator;

/**
 * Tests case for unique entity validator.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class UniqueEntityValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected function createRegistryMock($entityManagerName, $em)
    {
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->any())
            ->method('getManager')
            ->with($this->equalTo($entityManagerName))
            ->will($this->returnValue($em));

        return $registry;
    }

    protected function createRepositoryMock()
    {
        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->setMethods(array('findByCustom', 'find', 'findAll', 'findOneBy', 'findBy', 'getClassName'))
            ->getMock()
        ;

        return $repository;
    }

    protected function createEntityManagerMock($repositoryMock)
    {
        $em = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->getMock()
        ;
        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repositoryMock))
        ;

        $classMetadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $classMetadata
            ->expects($this->any())
            ->method('hasField')
            ->will($this->returnValue(true))
        ;
        $refl = $this->getMockBuilder('Doctrine\Common\Reflection\StaticReflectionProperty')
            ->disableOriginalConstructor()
            ->setMethods(array('getValue'))
            ->getMock()
        ;
        $refl
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue(true))
        ;
        /* @var \Doctrine\ORM\Mapping\ClassMetadata $classMetadata */
        $classMetadata->reflFields = array('name' => $refl);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($classMetadata))
        ;

        return $em;
    }

    protected function createValidatorFactory($uniqueValidator)
    {
        $validatorFactory = $this->getMock('Symfony\Component\Validator\ConstraintValidatorFactoryInterface');
        $validatorFactory->expects($this->any())
            ->method('getInstance')
            ->with($this->isInstanceOf('Sonatra\Bundle\DoctrineExtensionsBundle\Validator\Constraints\UniqueEntity'))
            ->will($this->returnValue($uniqueValidator));

        return $validatorFactory;
    }

    protected function createValidator($entityManagerName, $em, $validateClass = null, $uniqueFields = null, $errorPath = null, $repositoryMethod = 'findBy', $ignoreNull = true, array $filters = array(), $all = true)
    {
        if (!$validateClass) {
            $validateClass = 'Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdEntity';
        }
        if (!$uniqueFields) {
            $uniqueFields = array('name');
        }

        /* @var ManagerRegistry $registry */
        $registry = $this->createRegistryMock($entityManagerName, $em);

        $uniqueValidator = new UniqueEntityValidator($registry);

        $metadata = new ClassMetadata($validateClass);
        $constraint = new UniqueEntity(array(
            'fields' => $uniqueFields,
            'em' => $entityManagerName,
            'errorPath' => $errorPath,
            'repositoryMethod' => $repositoryMethod,
            'ignoreNull' => $ignoreNull,
            'filters' => $filters,
            'allFilters' => $all,
        ));
        $metadata->addConstraint($constraint);

        $metadataFactory = new FakeMetadataFactory();
        $metadataFactory->addMetadata($metadata);
        /* @var ConstraintValidatorFactoryInterface $validatorFactory */
        $validatorFactory = $this->createValidatorFactory($uniqueValidator);
        $contextFactory = new ExecutionContextFactory(new IdentityTranslator(), null);

        return new RecursiveValidator($contextFactory, $metadataFactory, $validatorFactory, array());
    }

    protected function createSchema($em)
    {
        /* @var EntityManagerInterface $em */
        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(array(
                $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdEntity'),
                $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\DoubleNameEntity'),
                $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\CompositeIntIdEntity'),
                $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\AssociationEntity'),
            ));
    }

    public function testConstraintIsNotUniqueEntity()
    {
        /* @var ManagerRegistry $registry */
        /* @var Constraint $constraint */

        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        $registry = $this->createRegistryMock($entityManagerName, $em);
        $validator = new UniqueEntityValidator($registry);
        $constraint = $this->getMockForAbstractClass('Symfony\Component\Validator\Constraint');
        $entity = new SingleIntIdEntity(1, 'Foo');

        $this->setExpectedException('\Sonatra\Bundle\DoctrineExtensionsBundle\Exception\UnexpectedTypeException');
        $validator->validate($entity, $constraint);
    }

    public function testConstraintWrongFieldType()
    {
        /* @var ManagerRegistry $registry */

        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        $registry = $this->createRegistryMock($entityManagerName, $em);
        $validator = new UniqueEntityValidator($registry);
        $constraint = new UniqueEntity(array('fields' => 42));
        $entity = new SingleIntIdEntity(1, 'Foo');

        $this->setExpectedException('\Sonatra\Bundle\DoctrineExtensionsBundle\Exception\UnexpectedTypeException');
        $validator->validate($entity, $constraint);
    }

    public function testConstraintWrongErrorPath()
    {
        /* @var ManagerRegistry $registry */

        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        $registry = $this->createRegistryMock($entityManagerName, $em);
        $validator = new UniqueEntityValidator($registry);
        $constraint = new UniqueEntity(array('fields' => 'name', 'errorPath' => 42));
        $entity = new SingleIntIdEntity(1, 'Foo');

        $this->setExpectedException('\Sonatra\Bundle\DoctrineExtensionsBundle\Exception\UnexpectedTypeException');
        $validator->validate($entity, $constraint);
    }

    public function testConstraintHasNotField()
    {
        /* @var ManagerRegistry $registry */

        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        $registry = $this->createRegistryMock($entityManagerName, $em);
        $validator = new UniqueEntityValidator($registry);
        $constraint = new UniqueEntity(array('fields' => 'name'));
        $constraint->fields = array();
        $entity = new SingleIntIdEntity(1, 'Foo');

        $this->setExpectedException('\Sonatra\Bundle\DoctrineExtensionsBundle\Exception\ConstraintDefinitionException');
        $validator->validate($entity, $constraint);
    }

    public function testConstraintHasNotExistingField()
    {
        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        $this->createSchema($em);
        $validator = $this->createValidator($entityManagerName, $em, null, '42');
        $entity1 = new SingleIntIdEntity(1, 'Foo');

        $this->setExpectedException('\Sonatra\Bundle\DoctrineExtensionsBundle\Exception\ConstraintDefinitionException');
        $validator->validate($entity1);
    }

    public function testValidateUniqueness()
    {
        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        $this->createSchema($em);
        $validator = $this->createValidator($entityManagerName, $em);

        $entity1 = new SingleIntIdEntity(1, 'Foo');
        $violationsList = $validator->validate($entity1);
        $this->assertEquals(0, $violationsList->count(), 'No violations found on entity before it is saved to the database.');

        $em->persist($entity1);
        $em->flush();

        $violationsList = $validator->validate($entity1);
        $this->assertEquals(0, $violationsList->count(), 'No violations found on entity after it was saved to the database.');

        $entity2 = new SingleIntIdEntity(2, 'Foo');

        $violationsList = $validator->validate($entity2);
        $this->assertEquals(1, $violationsList->count(), 'Violation found on entity with conflicting entity existing in the database.');

        /* @var ConstraintViolationInterface $violation */
        $violation = $violationsList[0];
        $this->assertEquals('This value is already used.', $violation->getMessage());
        $this->assertEquals('name', $violation->getPropertyPath());
        $this->assertEquals('Foo', $violation->getInvalidValue());
    }

    public function testValidateCustomErrorPath()
    {
        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        $this->createSchema($em);
        $validator = $this->createValidator($entityManagerName, $em, null, null, 'bar');

        $entity1 = new SingleIntIdEntity(1, 'Foo');

        $em->persist($entity1);
        $em->flush();

        $entity2 = new SingleIntIdEntity(2, 'Foo');

        $violationsList = $validator->validate($entity2);
        $this->assertEquals(1, $violationsList->count(), 'Violation found on entity with conflicting entity existing in the database.');

        /* @var ConstraintViolationInterface $violation */
        $violation = $violationsList[0];
        $this->assertEquals('This value is already used.', $violation->getMessage());
        $this->assertEquals('bar', $violation->getPropertyPath());
        $this->assertEquals('Foo', $violation->getInvalidValue());
    }

    public function testValidateUniquenessWithNull()
    {
        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        $this->createSchema($em);
        $validator = $this->createValidator($entityManagerName, $em);

        $entity1 = new SingleIntIdEntity(1, null);
        $entity2 = new SingleIntIdEntity(2, null);

        $em->persist($entity1);
        $em->persist($entity2);
        $em->flush();

        $violationsList = $validator->validate($entity1);
        $this->assertEquals(0, $violationsList->count(), 'No violations found on entity having a null value.');
    }

    public function testValidateUniquenessWithIgnoreNull()
    {
        $entityManagerName = 'foo';
        $validateClass = 'Symfony\Bridge\Doctrine\Tests\Fixtures\DoubleNameEntity';
        $em = DoctrineTestHelper::createTestEntityManager();
        $this->createSchema($em);
        $validator = $this->createValidator($entityManagerName, $em, $validateClass, array('name', 'name2'), 'bar', 'findby', false);

        $entity1 = new DoubleNameEntity(1, 'Foo', null);
        $violationsList = $validator->validate($entity1);
        $this->assertEquals(0, $violationsList->count(), 'No violations found on entity before it is saved to the database.');

        $em->persist($entity1);
        $em->flush();

        $violationsList = $validator->validate($entity1);
        $this->assertEquals(0, $violationsList->count(), 'No violations found on entity after it was saved to the database.');

        $entity2 = new DoubleNameEntity(2, 'Foo', null);

        $violationsList = $validator->validate($entity2);
        $this->assertEquals(1, $violationsList->count(), 'Violation found on entity with conflicting entity existing in the database.');

        /* @var ConstraintViolationInterface $violation */
        $violation = $violationsList[0];
        $this->assertEquals('This value is already used.', $violation->getMessage());
        $this->assertEquals('bar', $violation->getPropertyPath());
        $this->assertEquals('Foo', $violation->getInvalidValue());
    }

    public function testValidateUniquenessAfterConsideringMultipleQueryResults()
    {
        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        $this->createSchema($em);
        $validator = $this->createValidator($entityManagerName, $em);

        $entity1 = new SingleIntIdEntity(1, 'foo');
        $entity2 = new SingleIntIdEntity(2, 'foo');

        $em->persist($entity1);
        $em->persist($entity2);
        $em->flush();

        $violationsList = $validator->validate($entity1);
        $this->assertEquals(1, $violationsList->count(), 'Violation found on entity with conflicting entity existing in the database.');

        $violationsList = $validator->validate($entity2);
        $this->assertEquals(1, $violationsList->count(), 'Violation found on entity with conflicting entity existing in the database.');
    }

    public function testValidateUniquenessUsingCustomRepositoryMethod()
    {
        $entityManagerName = 'foo';
        $repository = $this->createRepositoryMock();
        $repository->expects($this->once())
            ->method('findByCustom')
            ->will($this->returnValue(array()))
        ;
        $em = $this->createEntityManagerMock($repository);
        $validator = $this->createValidator($entityManagerName, $em, null, array(), null, 'findByCustom');

        $entity1 = new SingleIntIdEntity(1, 'foo');

        $violationsList = $validator->validate($entity1);
        $this->assertEquals(0, $violationsList->count(), 'Violation is using custom repository method.');
    }

    public function testValidateUniquenessWithUnrewoundArray()
    {
        $entity = new SingleIntIdEntity(1, 'foo');

        $entityManagerName = 'foo';
        $repository = $this->createRepositoryMock();
        $repository->expects($this->once())
            ->method('findByCustom')
            ->will(
                $this->returnCallback(function () use ($entity) {
                        $returnValue = array(
                            $entity,
                        );
                        next($returnValue);

                        return $returnValue;
                    })
            )
        ;
        $em = $this->createEntityManagerMock($repository);
        $validator = $this->createValidator($entityManagerName, $em, null, array(), null, 'findByCustom');

        $violationsList = $validator->validate($entity);
        $this->assertCount(0, $violationsList, 'Violation is using unrewound array as return value in the repository method.');
    }

    public function testAssociatedEntity()
    {
        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        $this->createSchema($em);
        $validator = $this->createValidator($entityManagerName, $em, 'Symfony\Bridge\Doctrine\Tests\Fixtures\AssociationEntity', array('single'));

        $entity1 = new SingleIntIdEntity(1, 'foo');
        $associated = new AssociationEntity();
        $associated->single = $entity1;

        $em->persist($entity1);
        $em->persist($associated);
        $em->flush();

        $violationsList = $validator->validate($associated);
        $this->assertEquals(0, $violationsList->count());

        $associated2 = new AssociationEntity();
        $associated2->single = $entity1;

        $em->persist($associated2);
        $em->flush();

        $violationsList = $validator->validate($associated2);
        $this->assertEquals(1, $violationsList->count());
    }

    public function testAssociatedEntityWithNull()
    {
        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        $this->createSchema($em);
        $validator = $this->createValidator($entityManagerName, $em, 'Symfony\Bridge\Doctrine\Tests\Fixtures\AssociationEntity', array('single'), null, 'findBy', false);

        $associated = new AssociationEntity();
        $associated->single = null;

        $em->persist($associated);
        $em->flush();

        $violationsList = $validator->validate($associated);
        $this->assertEquals(0, $violationsList->count());
    }

    public function testAssociatedCompositeEntity()
    {
        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        $this->createSchema($em);
        $validator = $this->createValidator($entityManagerName, $em, 'Symfony\Bridge\Doctrine\Tests\Fixtures\AssociationEntity', array('composite'));

        $composite = new CompositeIntIdEntity(1, 1, 'test');
        $associated = new AssociationEntity();
        $associated->composite = $composite;

        $em->persist($composite);
        $em->persist($associated);
        $em->flush();

        $this->setExpectedException(
            'Symfony\Component\Validator\Exception\ConstraintDefinitionException',
            'Associated entities are not allowed to have more than one identifier field'
        );
        $validator->validate($associated);
    }

    public function testDedicatedEntityManagerNullObject()
    {
        $uniqueFields = array('name');
        $entityManagerName = 'foo';

        /* @var ManagerRegistry $registry */
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $constraint = new UniqueEntity(array(
            'fields' => $uniqueFields,
            'em' => $entityManagerName,
        ));

        $uniqueValidator = new UniqueEntityValidator($registry);

        $entity = new SingleIntIdEntity(1, null);

        $this->setExpectedException(
            'Symfony\Component\Validator\Exception\ConstraintDefinitionException',
            'Object manager "foo" does not exist.'
        );

        $uniqueValidator->validate($entity, $constraint);
    }

    public function testEntityManagerNullObject()
    {
        $uniqueFields = array('name');

        /* @var ManagerRegistry $registry */
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $constraint = new UniqueEntity(array(
            'fields' => $uniqueFields,
        ));

        $uniqueValidator = new UniqueEntityValidator($registry);

        $entity = new SingleIntIdEntity(1, null);

        $this->setExpectedException(
            'Symfony\Component\Validator\Exception\ConstraintDefinitionException',
            'Unable to find the object manager associated with an entity of class "Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdEntity"'
        );

        $uniqueValidator->validate($entity, $constraint);
    }

    public function testDisableAllFilterAndReactivateAfter()
    {
        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        $em->getConfiguration()->addFilter('fooFilter1', 'Sonatra\Bundle\DoctrineExtensionsBundle\Tests\Fixtures\FooFilter');
        $em->getConfiguration()->addFilter('fooFilter2', 'Sonatra\Bundle\DoctrineExtensionsBundle\Tests\Fixtures\FooFilter');
        $em->getFilters()->enable('fooFilter1');
        $em->getFilters()->enable('fooFilter2');
        $this->createSchema($em);
        $validator = $this->createValidator($entityManagerName, $em);
        $entity1 = new SingleIntIdEntity(1, 'Foo');

        $this->assertCount(2, $em->getFilters()->getEnabledFilters());

        $violationsList = $validator->validate($entity1);

        $this->assertEquals(0, $violationsList->count());
        $this->assertCount(2, $em->getFilters()->getEnabledFilters());
    }

    public function testDisableOneFilterAndReactivateAfter()
    {
        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        $em->getConfiguration()->addFilter('fooFilter1', 'Sonatra\Bundle\DoctrineExtensionsBundle\Tests\Fixtures\FooFilter');
        $em->getConfiguration()->addFilter('fooFilter2', 'Sonatra\Bundle\DoctrineExtensionsBundle\Tests\Fixtures\FooFilter');
        $em->getFilters()->enable('fooFilter1');
        $em->getFilters()->enable('fooFilter2');
        $this->createSchema($em);
        $validator = $this->createValidator($entityManagerName, $em, null, null, null, 'findBy', true, array('fooFilter1'), false);
        $entity1 = new SingleIntIdEntity(1, 'Foo');

        $this->assertCount(2, $em->getFilters()->getEnabledFilters());

        $violationsList = $validator->validate($entity1);

        $this->assertEquals(0, $violationsList->count());
        $this->assertCount(2, $em->getFilters()->getEnabledFilters());
    }
}
