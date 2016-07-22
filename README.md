# GiBiLogic CrudBundle

A Symfony 2 bundle that contains simple CRUD-oriented services and helpers.

This bundle helps creating CRUD systems for Doctrine entities by:

* simplifying filtering and sorting operations
* handling the (optional) pagination
* making the hydration mode accessible to top-level methods

This bundle uses the following libraries/framework:

* Symfony 2 HTTP kernel
* Doctrine ORM

**ATTENTION -** Do **not** use the older 1.* version: it **won't be supported** any more. So, switch to the 2.* version and happy coding!

## Installation

Add this bundle to the composer.json of your application with the console command:

```bash
composer require gibilogic/crud-bundle
```

Or, if you are using `composer.phar`, use the console command:

```bash
php composer.phar require gibilogic/crud-bundle
```

Then add the bundle to your app's `AppKernel` class:

```php
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Gibilogic\CrudBundle\GibilogicCrudBundle(),
            // ...
        );

        // ...

        return $bundles;
    }

    // ...
}
```

## Configuration

No app configuration is needed; just install the bundle and create your entities classes (**repositories**, **services** and **controllers**).

## Usage

Click [here](Resources/doc/index.md) to read the full bundle's documentation.
