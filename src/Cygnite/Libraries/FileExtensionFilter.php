<?php
namespace Cygnite\Libraries;

// import SPL classes/interfaces into local scope
use DirectoryIterator,
    FilterIterator,
    RecursiveIterator,
    RecursiveDirectoryIterator,
    RecursiveIteratorIterator;

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
 * @Package                    :  Packages
 * @Sub Packages               :  Library
 * @Filename                   :  FileExtensionFilter
 * @Description                :  This library is used to filte file from given array
 * @Author                     :  Sanjoy Dey
 * @Copyright                  :  Copyright (c) 2013 - 2014,
 * @Link	               :  http://www.cygniteframework.com
 * @Since	               :  Version 1.0.6
 * @Filesource
 *
 */




if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class FileExtensionFilter extends FilterIterator
{
    // whitelist of file extensions
    protected $ext = array("php");

    public function __construct($dirOrIterator)
    {
        if ($dirOrIterator instanceof \RecursiveIterator) {
            $iterator = new \RecursiveIteratorIterator($dirOrIterator);
        } else {
            $iterator = $dirOrIterator;
        }

        parent::__construct($iterator);
    }	

 
    // an abstract method which must be implemented in subclass
    public function accept() {

	 $file = $this->getInnerIterator()->current();

        // If we somehow have something other than an SplFileInfo object, just 
        // return false
        if (!$file instanceof \SplFileInfo) {
            return false;
        }

        // If we have a directory, it's not a file, so return false
        if (!$file->isFile()) {
            return false;
        }

        // If not a PHP file, skip
        if ($file->getBasename('.php') == $file->getBasename()) {
            return false;
        }
		
	return in_array($this->getExtension(), $this->ext);
    }
}
