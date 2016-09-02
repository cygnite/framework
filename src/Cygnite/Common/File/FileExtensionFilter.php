<?php

/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Common\File;

// import SPL classes/interfaces into local scope
use FilterIterator;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class FileExtensionFilter extends FilterIterator
{
    // whitelist of file extensions
    protected $ext = ['php'];

    public function __construct($dirOrIterator)
    {
        $iterator = $dirOrIterator;
        if ($dirOrIterator instanceof \RecursiveIterator) {
            $iterator = new \RecursiveIteratorIterator($dirOrIterator);
        }

        parent::__construct($iterator);
    }

    // an abstract method which must be implemented in subclass
    public function accept()
    {
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
