<?php
namespace Cygnite\Common\SessionManager;

interface PacketInterface extends \ArrayAccess, \Iterator, \Countable
{
    const SEPARATOR = '.';

    /**
     * Retrieves offset value
     *
     * @param string $offset
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($offset = null, $default = null);

    /**
     * Sets value to offset
     *
     * @param string $offset
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($offset, $value = null);

    /**
     * Returns true if offset exists in bag
     *
     * @param string $offset
     *
     * @return bool
     */
    public function has($offset = null);

    /**
     * Removes offset from bag
     * If no offset set, removes all values
     *
     * @param string $offset attribute to remove from
     *
     * @return $this
     */
    public function delete($offset = null);

    /**
     * Returns all elements
     * If array passed, becomes bag content
     *
     * @param array $array overwrites values
     *
     * @return array
     */
    public function all($array = []);

    /**
     * Removes all elements
     *
     * @return $this
     */
    public function reset();
}
