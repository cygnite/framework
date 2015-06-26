<?php
namespace Cygnite\Mvc\View;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Class Output
 *
 * @package Cygnite\Mvc\View
 */
class Output
{
    private $output = [];

    private $name;

    /**
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param       $file
     * @param array $data
     * @return $this
     */
    public function buffer($file, $data = [])
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

    /**
     * @param $name
     * @param $value
     */
    public function setOutput($name, $value)
    {
        $this->output[$name] = $value;
    }

    /**
     * @param $name
     * @return null
     */
    public function getOutput($name)
    {
        return isset($this->output[$name]) ? $this->output[$name] : null;
    }

    /**
     * @return null
     */
    public function __toString()
    {
        return $this->getOutput($this->name);
    }

    /**
     * @return $this
     */
    public function clean()
    {
        $output = ob_get_contents();
        ob_get_clean();
        //ob_end_flush();

        $this->setOutput($this->name, $output);

        return $this;
    }
}