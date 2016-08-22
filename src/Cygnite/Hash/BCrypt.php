<?php

namespace Cygnite\Hash;

/**
 * Class BCrypt.
 */
class BCrypt implements HashInterface
{
    /**
     * Default cost for BCrypt hashing.
     *
     * @var int
     */
    protected $cost = 10;

    /**
     * @param \Clouser $callback
     *
     * @return static
     */
    public static function make(\Clouser $callback = null)
    {
        if ($callback instanceof \Closure) {
            return $callback(new static());
        }

        return new static();
    }

    /**
     * Set BCrypt Hashing cost.
     *
     * @param $cost
     *
     * @return $this
     */
    public function setHashingCost($cost)
    {
        $this->cost = (int) $cost;

        return $this;
    }

    /**
     * We will creating hash for the given string and return it.
     *
     * @param string $string
     * @param array  $arguments
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    public function create($string, array $arguments = [])
    {
        $cost = isset($arguments['cost']) ? $arguments['cost'] : $this->cost;

        if (isset($arguments['salt'])) {
            $hash = password_hash($string, PASSWORD_BCRYPT, ['cost' => $cost, 'salt' => $arguments['salt']]);
        } else {
            $hash = password_hash($string, PASSWORD_BCRYPT, ['cost' => $cost]);
        }

        if ($hash === false) {
            throw new \RuntimeException('BCrypt hashing support not available.');
        }

        return $hash;
    }

    /**
     * We will verify the given string(password) against hashed password.
     *
     * @param string $string
     * @param string $hash
     * @param array  $arguments
     *
     * @return bool
     */
    public function verify($string, $hash, array $arguments = [])
    {
        return password_verify($string, $hash);
    }

    /**
     * We will check if given hashed string has been
     * hashed using the given options.
     *
     * @param string $hashedString
     * @param array  $arguments
     *
     * @return bool
     */
    public function needReHash($hashedString, array $arguments = [])
    {
        $cost = isset($arguments['cost']) ? $arguments['cost'] : $this->cost;

        return password_needs_rehash($hashedString, PASSWORD_BCRYPT, ['cost' => $cost]);
    }
}
