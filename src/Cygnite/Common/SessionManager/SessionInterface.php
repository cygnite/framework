<?php
namespace Cygnite\Common\SessionManager;

interface SessionInterface
{
    public function save($key, $value);

    public function get($key = null, $defaultValue = null);

    public function trash($userData = null);
    
}