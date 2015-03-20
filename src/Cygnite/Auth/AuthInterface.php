<?php
namespace Cygnite\Auth;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

interface AuthInterface
{
    /**
     * Abstract function verify($user, $password = null, $status = false); implemented in child class
     *
     * @param      $user
     * @param null $password
     * @param bool $status
     * @return mixed
     */
    public function verify($user, $password = null, $status = false);

    /**
     * Abstract function login(); implemented in child class
     * @return mixed
     */
    public function login();

    /**
     * Abstract function logout(); implemented in child class
     * @return mixed
     */
    public function logout();

    /**
     * Abstract function isLoggedIn($key); implemented in child class
     * @param $key
     * @return mixed
     */
    public function isLoggedIn($key);
}