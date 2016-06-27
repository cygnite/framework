<?php
namespace Cygnite\Mvc\View;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * trait Output
 *
 * @package Cygnite\Mvc\View
 */
trait Output
{
    /**
     * @param $path
     * @param $data
     * @return string
     */
    protected function renderView($path, $data)
    {
        $obLevel = ob_get_level();
        ob_start();
        extract($data);

        /*
         | We will try to include view file and check content into try catch block
         | so that if any exception occurs output buffer will get flush out.
        */
        try {
            include $path;
        } catch (Exception $e) {
            $this->handleViewException($e, $obLevel);
        }

        return ltrim(ob_get_clean());
    }

    /**
     * @param $e
     * @param $obLevel
     * @throws
     */
    protected function handleViewException($e, $obLevel)
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }
}
