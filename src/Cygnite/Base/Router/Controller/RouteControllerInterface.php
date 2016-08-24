<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Base\Router\Controller;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

/**
 * Class RouteControllerInterface.
 */
interface RouteControllerInterface
{
    /**
     * Set the controller as Route Controller
     * Cygnite Router knows how to respond to routes controller
     * request automatically.
     *
     * @param $controller
     *
     * @return $this
     */
    public function routeController($controller);

    /**
     * @param $actions
     *
     * @return array
     */
    public function setActions($actions);

    /**
     * @return array
     */
    public function getActions();
}
