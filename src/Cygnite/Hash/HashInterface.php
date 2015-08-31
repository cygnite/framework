<?php
namespace Cygnite\Hash;

interface HashInterface
{
    /**
     * We will creating hash for the given string and return it.
     *
     * @param  string  $string
     * @param  array   $arguments
     * @return string
     *
     * @throws \RuntimeException
     */
    public function create($string, array $arguments = []);

    /**
     * We will verify the given string(password) against hashed password
     *
     * @param  string  $string
     * @param  string  $hash
     * @param  array   $arguments
     * @return bool
     */
    public function verify($string, $hash, array $arguments = []);

    /**
     * We will check if given hashed string has been
     * hashed using the given options.
     *
     * @param  string  $hashedString
     * @param  array   $arguments
     * @return bool
     */
    public function needReHash($hashedString, array $arguments = []);
}
