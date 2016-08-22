<?php

namespace Cygnite\Common\SessionManager;

/**
 * Class Manager.
 */
class Manager extends AbstractPacket implements PacketInterface
{
    // Storage property to store session values
    protected $storage = [];

    /**
     * Constructor of Session Manager.
     *
     * @param array $storage
     */
    public function __construct(array $storage = [])
    {
        $this->all($storage);
    }

    /**
     * Get value from the storage if exists.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->all();
        }

        return $this->getStackReference($this->storage, explode(self::SEPARATOR, $key), $default);
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * @param $key
     *
     * @return null
     */
    public function __get($key)
    {
        return isset($this->storage[$key]) ? $this->storage[$key] : null;
    }

    /**
     * @param $key
     */
    public function __unset($key)
    {
        if (isset($this->storage[$key])) {
            unset($this->storage[$key]);
        }
    }

    /**
     * Sets value to session storage.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($key, $value = null)
    {
        if ($key === null) {
            array_push($this->storage, $value);

            return $this;
        }

        if (is_array($key)) {
            foreach ($key as $key => $value) {
                $this->storage[$key] = $value;
            }

            return $this;
        }

        $this->setValueToStack($this->storage, explode(self::SEPARATOR, $key), $value);

        return $this;
    }

    /**
     * We will check if session key exists
     * return boolean value.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key = null)
    {
        if ($key === null) {
            return $this->count() > 0;
        }

        $arr = &$this->getStackByReference($key);

        return is_array($arr) ? array_key_exists($key, $arr) : false;
    }

    /**
     * Remove key from the stack
     * if key given null we will remove all values.
     *
     * @param string $key attribute to remove from
     *
     * @return $this
     */
    public function delete($key = null)
    {
        if ($key === null) {
            $this->reset();

            return $this;
        }

        $arr = &$this->getStackByReference($key);

        if (is_array($arr) && array_key_exists($key, $arr)) {
            unset($arr[$key]);
        }

        return $this;
    }

    /**
     * Returns all elements
     * If array passed, we will store into storage property
     * as stack.
     *
     * @param array $array overwrites values
     *
     * @return array
     */
    public function all($array = [])
    {
        if (!empty($array)) {
            $this->storage = $array;
        }

        return $this->storage;
    }

    /**
     * Return the array reference.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function &getStackByReference(&$key)
    {
        $key = explode(self::SEPARATOR, $key);

        if (count($key) > 1) {
            $arr = &$this->getStackReference($this->storage, array_slice($key, 0, -1), false);
        } else {
            $arr = &$this->storage;
        }

        $key = array_slice($key, -1);
        $key = reset($key);

        return $arr;
    }

    /**
     * Returns array element matching key.
     *
     * @param array $array
     * @param array $keys
     * @param mixed $default
     *
     * @return string
     */
    protected function &getStackReference(&$array, $keys, $default = null)
    {
        $key = array_shift($keys);

        if (!isset($array[$key])) {
            return $default;
        }

        if (empty($keys)) {
            return $array[$key];
        }

        return $this->getStackReference($array[$key], $keys, $default);
    }

    /**
     * Sets array elements value.
     *
     * @param array $array
     * @param array $keys
     * @param mixed $value
     *
     * @return mixed
     */
    protected function setValueToStack(&$array, $keys, $value)
    {
        $k = array_shift($keys);

        if (is_scalar($array)) {
            $array = (array) $array;
        }

        if (!isset($array[$k])) {
            $array[$k] = null;
        }

        if (empty($keys)) {
            return $array[$k] = &$value;
        }

        return $this->setValueToStack($array[$k], $keys, $value);
    }
}
