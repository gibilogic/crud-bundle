## FlashableTrait

It's quite tedious writing the same methods inside every controller to add user flash messages; so we decided to move everything into a [PHP Trait](http://php.net/manual/en/language.oop5.traits.php).
 
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
 * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
 * @param string $type
 * @param string $message
 */
protected function addUserFlash(SessionInterface $session, $type, $message)
{
    $session->getFlashBag()->add($type, $message);
}
```
