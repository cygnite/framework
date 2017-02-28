<?php
namespace Cygnite\Tests\Router;

use Cygnite\Mvc\Controller\AbstractBaseController;

class UserController extends AbstractBaseController
{
    public function getIndexAction()
    {
        echo "Hello User";
    }

    public function getNewAction()
    {
        echo "Hello New";
    }
}
