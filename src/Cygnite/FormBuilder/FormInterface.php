<?php

namespace Cygnite\FormBuilder;

use Cygnite\Http\Requests\Request;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}

/**
 * Class FormInterface.
 */
interface FormInterface
{
    /**
     * Create a form instance and return it.
     *
     * @param Closure $callback
     *
     * @return mixed
     */
    public static function make(\Closure $callback = null) : FormInterface;

    /**
     * Bind a model or entity object to Form.
     *
     * @param $entity
     */
    public function bind($entity) : FormInterface;

    /**
     * Set Http Request object.
     *
     * @param $request
     */
    public function setRequest(Request $request) : FormInterface;

    /**
     * @return mixed
     */
    public function isValidRequest();

    /**
     * @param       $type
     * @param       $key
     * @param array $values
     *
     * @return mixed
     */
    public function addElement(string $type, string $key, array $values = []) : FormInterface;

    /**
     * @param array $elements
     *
     * @return mixed
     */
    public function addElements(array $elements = []) : FormInterface;

    /**
     * Get the html form.
     *
     * @return mixed
     */
    public function getForm();

    /**
     * If you wish to get only html elements.
     */
    public function getHtmlElements() : string;
}
