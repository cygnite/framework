<?php
namespace Cygnite\Bootstrappers;

/**
 * Class Paths.
 * Define all the application paths into object.
 * @package Cygnite\Bootstrappers
 */
class Paths implements \ArrayAccess
{
    private $paths = [];

    /** Set all paths into object.
     * @param array $paths
     */
    public function __construct(array $paths)
    {
        foreach ($paths as $key => $value) {
            $this->paths[$key] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return bool|void
     */
    public function offsetExists($offset) : bool
    {
        return isset($this->paths[$offset]);
    }

    /**
     * @param $offset
     * @return null
     */
    public function offsetGet($offset)
    {
        return isset($this->paths[$offset]) ? $this->paths[$offset] : null;
    }

    /**
    * @param $offset
    * @param $value
    * @throws \InvalidArgumentException
    */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            throw new \InvalidArgumentException("Offset cannot be empty");
        }

        $this->paths[$offset] = realpath($value);
    }

    /**
     * @param $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->paths[$offset]);
    }

    /**
     * Return all paths as array
     * @return array
     */
    public function all() : array
    {
        return $this->paths;
    }
}