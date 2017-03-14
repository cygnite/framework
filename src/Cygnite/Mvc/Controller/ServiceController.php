<?php

/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Mvc\Controller;

/**
 * ServiceController.
 *
 * Extend the features of AbstractBaseController.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class ServiceController extends AbstractBaseController implements ServiceControllerInterface
{
    /**
     * The container's bind data.
     *
     * @var array
     */
    private $service = [];

    public function __construct()
    {
    }



    /**
     * Get a data by key.
     *
     * @param $key
     *
     * @throws InvalidArgumentException
     *
     * @return
     */
    public function &__get($key)
    {
        if (!isset($this->service[$key])) {
            throw new InvalidArgumentException(sprintf('Value "%s" is not defined.', $key));
        }

        return
            isset($this->service[$key]) &&
            is_callable($this->service[$key]) ?
                $this->service[$key]($this) :
                $this->service[$key];
    }

    /**
     * Assigns a value to the specified data.
     *
     * @param string The data key to assign the value to
     * @param mixed  The value to set
     */
    public function __set($key, $value)
    {
        $this->service[$key] = $value;
    }
}
