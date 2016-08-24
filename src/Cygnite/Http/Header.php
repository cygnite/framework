<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Http;

use Cygnite\Foundation\Collection;

/**
 * Class Header.
 */
class Header extends Collection
{
    //The list of HTTP request headers are not starting with "HTTP_" prefix
    protected static $specialCaseHeaders = [
        'AUTH_TYPE'       => true,
        'CONTENT_LENGTH'  => true,
        'CONTENT_TYPE'    => true,
        'PHP_AUTH_DIGEST' => true,
        'PHP_AUTH_PW'     => true,
        'PHP_AUTH_TYPE'   => true,
        'PHP_AUTH_USER'   => true,
    ];

    /**
     * Constructor.
     *
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        // Only add "HTTP_" server values
        foreach ($values as $name => $value) {
            $name = strtoupper($name);

            if (isset(self::$specialCaseHeaders[$name]) || strpos($name, 'HTTP_') === 0) {
                $this->set($name, $value);
            }
        }
    }

    /**
     * Set header values.
     *
     * @param $name
     * @param $values
     * @param bool $shouldReplace
     *
     * @return $this|void
     */
    public function add($name, $values, $shouldReplace = true)
    {
        $this->set($name, $values, $shouldReplace);
    }

    /**
     * Return only first header values if exists otherwise
     * return all header information.
     *
     * @param mixed $name
     * @param null  $default
     * @param bool  $onlyReturnFirst
     *
     * @return mixed|null
     */
    public function get($name, $default = null, $onlyReturnFirst = true)
    {
        if ($this->has($name)) {
            $value = $this->data[$this->normalizeName($name)];

            if ($onlyReturnFirst) {
                return $value[0];
            }
        } else {
            $value = $default;
        }

        return $value;
    }

    /**
     * Check if header key exists.
     *
     * @param $name
     *
     * @return bool
     */
    public function has($name)
    {
        return parent::has($this->normalizeName($name));
    }

    /**
     * Remove header key from the collection.
     *
     * @param $name
     *
     * @return $this|void
     */
    public function remove($name)
    {
        parent::remove($this->normalizeName($name));
    }

    /**
     * Set header information.
     *
     * @param $name
     * @param $value
     * @param bool $shouldReplace
     */
    public function set($name, $value, $shouldReplace = true)
    {
        $name = $this->normalizeName($name);
        $value = (array) $value;

        if ($shouldReplace || !$this->has($name)) {
            parent::add($name, $value);
        } else {
            parent::add($name, array_merge($this->data[$name], $value));
        }
    }

    /**
     * Normalizes a key string.
     *
     * @param $name
     *
     * @return string
     */
    protected function normalizeName($name)
    {
        $name = strtr(strtolower($name), '_', '-');

        if (strpos($name, 'http-') === 0) {
            $name = substr($name, 5);
        }

        return $name;
    }
}
