<?php
namespace Cygnite\FormBuilder;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

/**
 * Class FormInterface
 *
 * @package Cygnite\FormBuilder
 */
interface FormInterface
{
    /**
     * Create a form instance and return it
     *
     * @param Closure $callback
     * @return mixed
     */
    public static function make(\Closure $callback = null);

    /**
     * Alias method of make()
     *
     * @param Closure $callback
     * @return mixed
     */
    public static function instance(\Closure $callback = null);

    /**
     * @return mixed
     */
    public function isValidRequest();

    /**
     * @param       $type
     * @param       $key
     * @param array $values
     * @return mixed
     */
    public function addElement($type, $key, $values = []);

    /**
     * @param array $elements
     * @return mixed
     */
    public function addElements($elements = []);

    /**
     * Get the html form
     *
     * @return mixed
     */
    public function getForm();

    /**
     * If you wish to get only html elements
     */
    public function getHtmlElements();
}
