<?php
namespace Cygnite\Mvc\View;

use Cygnite\Facade\Facade;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class Output extends Facade
{

   /*protected function startBuffer()
    {
        ob_start();
        return $this;
    }*/

    protected function load($file, $data = array())
    {
        ob_start();

        if (!empty($data) || $data !== '') {
            extract($data);
        }

        if (is_readable($file)) {
            include_once $file;
        }

        return $this;
    }

    protected function endBuffer()
    {
        $output = ob_get_contents();
        ob_get_clean();
        ob_end_flush();

        return $output;
    }
}