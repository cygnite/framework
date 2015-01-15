<?php
namespace Cygnite\Common\File\Upload;

interface FileUploadInterface
{
    public function setUploadSize($size);
    
    public function getRootDir();
        
    public function getName();
    
    public function getTempName();
    
    public function getType();
    
    public function getSize();
    
    public function getError();
}
