<?php

namespace Cygnite\Mvc\View\Exceptions;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class ViewNotFoundException extends \Exception
{
}
