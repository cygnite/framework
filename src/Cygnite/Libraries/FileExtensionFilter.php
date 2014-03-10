<?php
namespace Cygnite\Libraries;

use FilterIterator;


if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class FileExtensionFilter extends FilterIterator
{
    // whitelist of file extensions
    protected $ext = ["php"];
 
    // an abstract method which must be implemented in subclass
    public function accept() {
        return in_array($this->getExtension(), $this->ext);
    }
}
