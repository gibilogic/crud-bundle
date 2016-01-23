# GiBiLogic CrudBundle

A Symfony 2 bundle that contains simple CRUD-oriented services and helpers.

This bundle helps creating CRUD systems for Doctrine entities by:

* simplifying filtering and sorting operations
* handling the (optional) pagination
* making the hydration mode accessible to top-level methods (for REST-oriented APIs)

This bundle uses the following libraries/framework:

* Symfony 2
* Doctrine ORM
* Twig

## Installation

Add this bundle to the composer.json of your application with the console command:

```bash
composer require gibilogic/crud-bundle dev-2.0-dev
```

Or, if you are using `composer.phar`, use the console command:

```bash
php composer.phar require gibilogic/crud-bundle dev-2.0-dev
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

The 3 main classes are:

* `Gibilogic\CrudBundle\Utility\FlashableTrait`: a [PHP Trait](http://php.net/manual/en/language.oop5.traits.php) to be used anywhere you need to add user flash messages
* `Gibilogic\CrudBundle\Entity\EntityRepository`: the base class for each repository of your app's entities, with a lot of work already done for you (filtering, sorting, custom filters, pagination, etc.) 
* `Gibilogic\CrudBundle\Utility\EntityService`: the base class for each manager of your app's entities, with methods to read, save and remove entities (plus session-side saving of filters and sorting)

### FlashableTrait

We found quite tedious to write inside every controller the same methods to add user flash messages; so we decided to move everything into a [PHP Trait](http://php.net/manual/en/language.oop5.traits.php).
 
First of all, import the trait inside your controller(s):

```php
namespace AppBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Gibilogic\CrudBundle\Utility\FlashableTrait;

/**
 * @Route("/admin/category")
 * @Security("has_role('ROLE_ADMIN')")
 */
class CategoryController extends Controller
{
    use FlashableTrait;

    // ...
}
```

Then you will be able to call the following methods:

* `addNoticeFlash` (to add a notice-like flash message)
* `addWarningFlash` (to add a warning-like flash message)
* `addErrorFlash` (to add an error-like flash message)

These methods require two parameters:

* an instance of the current session (taken from `$request->getSession()`, for example)
* the string message that needs to be shown

You can also directly call the base `addUserFlash` method:

```php
/**
 * Adds a flash message to the current user's session.
 *
 * @param \Symfony\Component\HttpFoundation\Session\Session $session
 * @param string $type
 * @param string $message
 */
protected function addUserFlash(Session $session, $type, $message)
{
    $session->getFlashBag()->add($type, $message);
}
```

### EntityRepository

After creating your entity (`Category` class, for example), create its repository by extending the `Gibilogic\CrudBundle\Entity\EntityRepository` class:

```php
namespace AppBundle\Entity\Repository;

use Gibilogic\CrudBundle\Entity\EntityRepository;

/**
 * CategoryRepository class.
 */
class CategoryRepository extends EntityRepository
{
    // ...
}
```

#### Configuration by override

You'll be able to override these 3 methods:

```php
/**
 * Creates the base query builder for your entity.
 * In this example, we've added a join with the category's parent category.
 */
protected function getBaseQueryBuilder()
{
    return $this->createQueryBuilder('e')
        ->addSelect('p')
        ->leftJoin('e.parent', 'p');
}

/**
 * A list of fields available to the sorting system; defaults to 'id' only.
 * Invalid fields will be ignored and the default sorting will be applied.
 */
protected function getSortableFields()
{
    return array('id' => true, 'name' => true, 'parent' => true, 'createdAt' => true);
}

/**
 * Default sorting rules; you can specify one or more fields.
 * They must be enabled inside the "getSortableFields" method.
 */
protected function getDefaultSorting()
{
    return array('name' => 'asc');
}
```

Fields inside the `getSortableFields` and `getDefaultSorting` methods are not limited to the main entity; you could set as default sorting a complex rule by using also the parent category:

```php
protected function getDefaultSorting()
{
    return array('p.name' => 'asc', 'e.name' => 'asc');
}
```

*NOTE -* Fields without an entity alias (like "name" or "parent") will be automatically prepended with the default alias "e" (thus getting "e.name" and "e.parent").

#### Methods

The repository gives you access to the following methods:

* `getEntity($id, $hydrationMode = AbstractQuery::HYDRATE_OBJECT)`
* `getEntityBy(array $filters, $hydrationMode = AbstractQuery::HYDRATE_OBJECT)`
* `getEntities($options = array(), $hydrationMode = AbstractQuery::HYDRATE_OBJECT)`
* `getPaginatedEntities($options = array(), $hydrationMode = AbstractQuery::HYDRATE_OBJECT)`

The first two (`getEntity` and `getEntityBy`) extract and return a single entity from the database; you can specify the hydration mode for both of them (quite usefult when you don't need the ORM overhead).

##### getEntityBy

The `getEntityBy` method accepts also an array of filters; for example, if you want to load the category with name "Toys":

```php
$categoryRepository->getEntityBy([
    'name' => 'Toys'
]);
```

As you can see, the entity alias is not mandatory for the entity's properties; but what if you need to load a category by its parent category's name? Easy:

```php
$categoryRepository->getEntityBy([
    'p.name' => 'Fun stuff'
]);
```

This will work thanks to our overridden `getBaseQueryBuilder` that includes the parent category with alias defined as "p".

##### getEntities

You can do the same if you need to load a filtered list of entities; just use the `getEntities` method:

```php
$categoryRepository->getEntities([
    'filters' => ['enabled' => true]
]);
```

The `$options` parameter of the `getEntities` method accepts also one or more sorting rules:

```php
$productRepository->getEntities([
    'filters' => ['published' => true],
    'sorting' => ['createdAt' => 'desc']
]);
```

If you don't specify any sorting rules, the repository will use the default ones (as specified inside the `getDefaultSorting` method).

##### getPaginatedEntities

The `getPaginatedEntities` method works like the `getEntities` one; it also adds pagination to the list by using the `Doctrine\ORM\Tools\Pagination\Paginator` class.

For paginating a list of entities, the methods needs these parameters:

* `elementsPerPage`: the number of entities to be loaded on each page
* `page`: the current page number (defaults to 1)

For example, to make our products list filtered, sorted and paginated, you should write something like the following piece of code:

```php
$productRepository->getPaginatedEntities([
    'elementsPerPage' => 20,
    'page' => 2,
    'filters' => ['published' => true],
    'sorting' => ['createdAt' => 'desc']
]);
```

As you can see, we have requested the second page of our list of products, filtered by published products only and sorted by their creation date.

#### Custom filters

Inside the repository you can define custom filters; let's add the famous and mystifying "foo" filter:

```php
namespace AppBundle\Entity\Repository;

use Gibilogic\CrudBundle\Entity\EntityRepository;

/**
 * CategoryRepository class.
 */
class CategoryRepository extends EntityRepository
{
    // ...

    /**
     * Applies the "foo" filter to the query builder.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param mixed $value
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function addFooFilter(QueryBuilder $queryBuilder, $value)
    {
        // Your filer's logic
    
        return $queryBuilder;
    }

    // ...
}
```

You'll be able to use it like any other filter:

```php
$productRepository->getEntities([
    'filters' => ['foo' => 'bar']
]);
```

For example, let's define the "root" filter for our categories:

```php
namespace AppBundle\Entity\Repository;

use Gibilogic\CrudBundle\Entity\EntityRepository;

/**
 * CategoryRepository class.
 */
class CategoryRepository extends EntityRepository
{
    // ...

    /**
     * Applies the "root" filter to the query builder.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param mixed $value
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function addRootFilter(QueryBuilder $queryBuilder, $value)
    {
        if (null === $value) {
            return $queryBuilder;
        }
    
        if ($value) {
            $queryBuilder->andWhere('e.parent is null');
        } else {
            $queryBuilder->andWhere('e.parent is not null');
        }
    
        return $queryBuilder;
    }

    // ...
}
```

This filter will accept the following values:

* A `null` value will ignore the filter
* A `true` value will exclude all the non-root categories (ie, whose parent is not null)
* A `false` value will exclude all the root categories (ie, whose parent is null)

*WARNING -* When defining a custom filter, always remember to add the `return $queryBuilder;` instruction to support its [fluent interface](https://en.wikipedia.org/wiki/Fluent_interface).
 
### EntityService

**Work in Progress**
