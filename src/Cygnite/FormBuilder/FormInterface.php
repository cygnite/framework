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
     * @return mixed
     */
    public function getForm();
}
