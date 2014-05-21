Sonatra DoctrineExtensionsBundle Doctrine callback validation
=============================================================

## Prerequisites

[Installation and Configuration](index.md)

## Doctrine callback validation

It is possible to execute queries in doctrine for validation constraints.

The entity:

``` php
<?php

namespace Acme\BlogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sonatra\Bundle\DoctrineExtensionsBundle\Validator as SValidator;

/**
 * @ORM\Entity
 * @SValidator\DoctrineCallback(methods={{"Acme\BlogBundle\Validator\MyEntityValidator", "isMyEntityValid"}})
 */
class MyEntity
{
    // code...
}
```

The validation class:

``` php
<?php

namespace Acme\BlogBundle\Validator;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\ExecutionContext;
use Acme\BlogBundle\Entity\MyEntity;

class MyEntityValidator
{
    static public function isMyEntityValid(MyEntity $entity, ExecutionContext $context, EntityManager $em = null)
    {
        $entities = $em->createQuery("SELECT e FROM AcmeBlogBundle:MyEntity u WHERE u.name = :name")
            ->setParameter("name", $user->getName())
            ->getResult();

        if (count($entities) > 0) {
            $context->addViolationAtSubPath('myentity', 'This name already exist', array(), null);
        }
    }
}
```
