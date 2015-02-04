<?php
namespace Cygnite\FormBuilder;

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
 * @package                 :  Packages
 * @subpackages             :  Common
 * @filename                :  FormInterface
 * @description             :  Form Interface to declare form methods
 * @author                  :  Cygnite Dev Team
 * @copyright               :  Copyright (c) 2013 - 2014,
 * @link                    :  http://www.cygniteframework.com
 * @since                   :  Version 1.0
 * @FileSource
 *
 */
interface FormInterface
{
    public function  addElement($type, $key, $values = array());

    public function addElements($elements = array());

    public function getForm();
}