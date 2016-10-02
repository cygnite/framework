<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Mvc;

use Cygnite\Container\Exceptions\ContainerException;

/**
 * trait ControllerViewBridgeTrait.
 */
trait ControllerViewBridgeTrait
{
    public $validFlashMessage = ['setFlash', 'hasFlash', 'getFlash', 'hasError'];

    /**
     * @param $method
     * @param $arguments
     *
     * @return AbstractBaseController|mixed
     */
    public function setFlashMessage($method, $arguments)
    {
        $flashSession = $this->get('cygnite.common.session-manager.flash.flash-message');

        if ($method == 'setFlash') {
            $this->_call($flashSession, $method, $arguments);

            return $this;
        } else {
            return $this->_call($flashSession, $method, $arguments);
        }
    }

    /**
     * Returns registered item from container.
     *
     * @param $class
     * @return mixed
     * @throws ContainerException
     */
    public function get($class)
    {
        if (!$this->container()->has($class)) {
            throw new ContainerException("Given class $class is not registered in container.");
        }

        return $this->container()->resolve($class);
    }

    /**
     * @param       $instance
     * @param       $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function _call($instance, $method, $arguments = [])
    {
        return call_user_func_array([$instance, $method], $arguments);
    }
}
