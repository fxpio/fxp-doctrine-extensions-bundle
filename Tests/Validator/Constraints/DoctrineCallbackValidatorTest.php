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
use Sonatra\Bundle\DoctrineExtensionsBundle\Tests\Fixtures\FooCallbackValidatorObject;
use Sonatra\Bundle\DoctrineExtensionsBundle\Validator\Constraints\DoctrineCallback;
use Sonatra\Bundle\DoctrineExtensionsBundle\Validator\Constraints\DoctrineCallbackValidator;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Tests case for doctrine callback validator.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DoctrineCallbackValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var DoctrineCallbackValidator
     */
    protected $validator;

    protected function setUp()
    {
        $entityManagerName = 'foo';
        $em = DoctrineTestHelper::createTestEntityManager();
        /* @var ManagerRegistry $registry */
        $registry = $this->createRegistryMock($entityManagerName, $em);
        $context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface', array(), array(), '', false);
        $this->context = $context;
        $this->validator = new DoctrineCallbackValidator($registry);
        /* @var ExecutionContextInterface $context */
        $this->validator->initialize($context);
    }

    protected function tearDown()
    {
        $this->context = null;
        $this->validator = null;
    }

    protected function createRegistryMock($entityManagerName, $em)
    {
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->any())
            ->method('getManager')
            ->with($this->equalTo($entityManagerName))
            ->will($this->returnValue($em));

        return $registry;
    }

    public function testNullIsValid()
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate(null, new DoctrineCallback('foo'));
    }

    public function testSingleMethod()
    {
        $object = new FooCallbackValidatorObject();
        $constraint = new DoctrineCallback('validate');

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with('My message', array(
                    '{{ value }}' => 'foobar',
                ));

        $this->validator->validate($object, $constraint);
    }

    public function testSingleMethodExplicitName()
    {
        $object = new FooCallbackValidatorObject();
        $constraint = new DoctrineCallback(array('callback' => 'validate'));

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with('My message', array(
                    '{{ value }}' => 'foobar',
                ));

        $this->validator->validate($object, $constraint);
    }

    public function testSingleStaticMethod()
    {
        $object = new FooCallbackValidatorObject();
        $constraint = new DoctrineCallback('validateStatic');

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with('Static message', array(
                    '{{ value }}' => 'baz',
                ));

        $this->validator->validate($object, $constraint);
    }

    public function testClosure()
    {
        $object = new FooCallbackValidatorObject();
        $constraint = new DoctrineCallback(function ($object, ExecutionContextInterface $context) {
            $context->addViolation('My message', array('{{ value }}' => 'foobar'), 'invalidValue');

            return false;
        });

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with('My message', array(
                    '{{ value }}' => 'foobar',
                ));

        $this->validator->validate($object, $constraint);
    }

    public function testClosureNullObject()
    {
        $constraint = new DoctrineCallback(function ($object, ExecutionContextInterface $context) {
            $context->addViolation('My message', array('{{ value }}' => 'foobar'), 'invalidValue');

            return false;
        });

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with('My message', array(
                    '{{ value }}' => 'foobar',
                ));

        $this->validator->validate(null, $constraint);
    }

    public function testClosureExplicitName()
    {
        $object = new FooCallbackValidatorObject();
        $constraint = new DoctrineCallback(array(
            'callback' => function ($object, ExecutionContextInterface $context) {
                    $context->addViolation('My message', array('{{ value }}' => 'foobar'), 'invalidValue');

                    return false;
                },
        ));

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with('My message', array(
                    '{{ value }}' => 'foobar',
                ));

        $this->validator->validate($object, $constraint);
    }

    public function testArrayCallable()
    {
        $object = new FooCallbackValidatorObject();
        $constraint = new DoctrineCallback(array('Sonatra\Bundle\DoctrineExtensionsBundle\Tests\Fixtures\FooCallbackValidatorClass', 'validateCallback'));

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with('Callback message', array(
                    '{{ value }}' => 'foobar',
                ));

        $this->validator->validate($object, $constraint);
    }

    public function testArrayCallableNullObject()
    {
        $constraint = new DoctrineCallback(array('Sonatra\Bundle\DoctrineExtensionsBundle\Tests\Fixtures\FooCallbackValidatorClass', 'validateCallback'));

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with('Callback message', array(
                    '{{ value }}' => 'foobar',
                ));

        $this->validator->validate(null, $constraint);
    }

    public function testArrayCallableExplicitName()
    {
        $object = new FooCallbackValidatorObject();
        $constraint = new DoctrineCallback(array(
            'callback' => array('Sonatra\Bundle\DoctrineExtensionsBundle\Tests\Fixtures\FooCallbackValidatorClass', 'validateCallback'),
        ));

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with('Callback message', array(
                    '{{ value }}' => 'foobar',
                ));

        $this->validator->validate($object, $constraint);
    }

    /**
     * @expectedException \Sonatra\Bundle\DoctrineExtensionsBundle\Exception\UnexpectedTypeException
     */
    public function testExpectValidConstraint()
    {
        $object = new FooCallbackValidatorObject();
        /* @var Constraint $constraint */
        $constraint = $this->getMockForAbstractClass('Symfony\Component\Validator\Constraint');

        $this->validator->validate($object, $constraint);
    }

    /**
     * @expectedException \Sonatra\Bundle\DoctrineExtensionsBundle\Exception\ConstraintDefinitionException
     */
    public function testExpectValidMethods()
    {
        $object = new FooCallbackValidatorObject();

        $this->validator->validate($object, new DoctrineCallback('foobar'));
    }

    /**
     * @expectedException \Sonatra\Bundle\DoctrineExtensionsBundle\Exception\ConstraintDefinitionException
     */
    public function testExpectValidCallbacks()
    {
        $object = new FooCallbackValidatorObject();

        $this->validator->validate($object, new DoctrineCallback(array('foo', 'bar')));
    }

    public function testConstraintGetTargets()
    {
        $constraint = new DoctrineCallback('foo');
        $targets = array(Constraint::CLASS_CONSTRAINT, Constraint::PROPERTY_CONSTRAINT);

        $this->assertEquals($targets, $constraint->getTargets());
    }

    public function testNoConstructorArguments()
    {
        new DoctrineCallback();
    }

    public function testAnnotationInvocationSingleValued()
    {
        $constraint = new DoctrineCallback(array('value' => 'validateStatic'));

        $this->assertEquals(new DoctrineCallback('validateStatic'), $constraint);
    }

    public function testAnnotationInvocationMultiValued()
    {
        $constraint = new DoctrineCallback(array('value' => array('Sonatra\Bundle\DoctrineExtensionsBundle\Tests\Fixtures\FooCallbackValidatorClass', 'validateCallback')));

        $this->assertEquals(new DoctrineCallback(array('Sonatra\Bundle\DoctrineExtensionsBundle\Tests\Fixtures\FooCallbackValidatorClass', 'validateCallback')), $constraint);
    }
}
