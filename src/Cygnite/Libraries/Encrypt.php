<?php
namespace Cygnite\Libraries;

use Cygnite\Helpers\Config;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}
/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3x or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @Package                   :  Packages
 * @Sub Packages              :  Library
 * @Filename                  :  Encrypt
 * @Description               :  This library used to encrypt and decrypt user input.
 * @Author                    :  Sanjoy Dey
 * @Copyright                 :  Copyright (c) 2013 - 2014,
 * @Link	                  :  http://www.cygniteframework.com
 * @Since	                  :  Version 1.0
 * @Filesource
 * @Warning                   :  Any changes in this library can cause abnormal behaviour of the framework
 *
 *
 */

class Encrypt
{

    private $secureKey;

    private $iv;

    private $value;

    private static $instance;

    /**
    * Constructor function
    * @false string - encryption key
    *
    */
    public function __construct()
    {
        $encryptKey = Config::get('global_config', 'cf_encryption_key');

        if (!is_null($encryptKey)) {

            $this->set($encryptKey);

            if (!function_exists('mcrypt_create_iv')) {
                throw new \BadFunctionCallException("Mcrypt extension library not loaded");
            }

            $this->iv = mcrypt_create_iv(32);

        } else {
            throw new \BadFunctionCallException(
                "Please check for encription key inside config file and autoload helper encrypt key is set or not."
            );
        }
    }


    public function set($encryptKey)
    {
        $this->secureKey = hash('sha256', $encryptKey, true);
    }

    public function get()
    {
        return $this->secureKey;
    }

    /*
     *  This function is to encrypt string
     * @access  public
     *  @false string
     * @return encrypted hash
     */
    public function encode($input)
    {
        if (!function_exists('mcrypt_create_iv')) {
            throw new \BadFunctionCallException("Mcrypt extension library not loaded");
        }

        $this->value = base64_encode(
            mcrypt_encrypt(
                MCRYPT_RIJNDAEL_256,
                $this->secureKey,
                $input,
                MCRYPT_MODE_ECB,
                $this->iv
            )
        );

        return $this->value;
    }

    /*
     * This function is to decrypt the encoded string
     * @access  public
     * @false string
     * @return decrypted string
     */
    public function decode($input)
    {
        $this->value = trim(
            mcrypt_decrypt(
                MCRYPT_RIJNDAEL_256,
                $this->secureKey,
                base64_decode($input),
                MCRYPT_MODE_ECB,
                $this->iv
            )
        );

        return $this->value;
    }

    public function __call($method, $arguments = array())
    {
        if ($method == 'instance') {
            return $this;
        }
    }

    public static function __callStatic($method, $arguments = array())
    {
        if ($method == 'instance') {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return call_user_func_array(array(self::$instance, $method), array($arguments));
        }
    }

    public function __destruct()
    {
        unset($this->secureKey);
        unset($this->iv);
    }
}
