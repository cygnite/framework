<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Http\Requests;

use Cygnite\Foundation\Collection;

/**
 * Class Files
 * @package Cygnite\Http\Requests
 */
class Files extends Collection
{
    /**
     * @param $name
     * @param $value
     * @return $this|void
     */
    public function add($name, $value)
    {
        parent::add($name, $value);
    }

    /**
     * @param mixed $name
     * @param null $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return parent::get($name, $default);
    }

    /**
     * @return array
     */
    public function all()
    {
        return parent::all();
    }
}