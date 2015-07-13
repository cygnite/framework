<?php
namespace Cygnite\Exception;

use Closure;
use Exception;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

interface ExceptionInterface
{
    /**
     * Display the given exception to the user.
     */
    public function run();

    public static function register(Closure $callback);
}
