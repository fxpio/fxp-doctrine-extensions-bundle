Getting Started With Sonatra DoctrineExtensionsBundle
=====================================================

## Prerequisites

This version of the bundle requires Symfony 2.4+.

## Installation

Installation is a quick, 2 step process:

1. Download Sonatra DoctrineExtensionsBundle using composer
2. Enable the bundle

### Step 1: Download Sonatra DoctrineExtensionsBundle using composer

Add Sonatra DoctrineExtensionsBundle in your composer.json:

``` js
{
    "require": {
        "sonatra/doctrine-extensions-bundle": "~1.0"
    }
}
```

Or tell composer to download the bundle by running the command:

``` bash
$ php composer.phar update sonatra/doctrine-extensions-bundle
```

Composer will install the bundle to your project's `vendor/sonatra` directory.

### Step 2: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Sonatra\Bundle\DoctrineExtensionsBundle\SonatraDoctrineExtensionsBundle(),
    );
}
```

### Next Steps

Now that you have completed the basic installation and configuration of the
Sonatra DoctrineExtensionsBundle, you are ready to learn about usages of the bundle.

The following documents are available:

- [Doctrine callback validation](doctrine_callback_validation.md)
