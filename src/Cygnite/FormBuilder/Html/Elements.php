<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\FormBuilder\Html;

use Closure;
use Cygnite\Common\Input;

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}
/**
 * Class Elements
 *
 * @package Cygnite\FormBuilder
 */
class Elements
{
    protected  $elements = [];

    protected $openTagHolder;

    /**
     * @param $key
     * @param $val
     * @return $this
     */
    protected function input($key, $val)
    {
        $extra = [
            'type' => strtolower(__FUNCTION__)
        ];

        return $this->composeElement($key, $val, $extra, true);
    }

    protected function custom($key, $val)
    {
        $extra = [
            'value' => (isset($val['name'])) ? $val['name'] : $key
        ];

        return $this->composeElement($key, $val, $extra);
    }

    /**
     * @param $key
     * @param $val
     * @return $this
     */
    protected function openTag($key, $val)
    {
        $exp = explode('_', $key);
        $this->openTagHolder = $exp[1];

        $extra = [
            'type' => $exp[0]
        ];

        return $this->composeElement($key, $val, $extra, true);
    }

    /**
     * @param $key
     * @return $this
     */
    protected function closeTag($key)
    {
        $exp = explode('_', $key);

        if ($this->openTagHolder == $exp[1]) {
            $this->elements[static::$formHolder[static::$formName]][$key] = "</$exp[0]>".PHP_EOL;
        }

        return $this;
    }

    /**
     * @param $key
     * @param $val
     * @return $this
     */
    protected function label($key, $val)
    {
        $extra = [
            'type' => strtolower(__FUNCTION__)
        ];

        return $this->composeElement($key, $val, $extra);
    }

    /**
     * @param       $key
     * @param       $attributes
     * @param array $extra
     * @param bool  $hasCloseTag
     * @return $this
     */
    private function composeElement($key, $attributes, $extra = [], $hasCloseTag = false)
    {
        $value = (isset($extra['value'])) ? $extra['value'] : $key;
        $type = (isset($extra['type'])) ? $extra['type'] : $key;

        if ($hasCloseTag) {
            $this->elements[static::$formHolder[static::$formName]][$key] =
                "<$type name='".$key."' ".$this->attributes($attributes)." />".PHP_EOL;

            return $this;
        }

        $this->elements[static::$formHolder[static::$formName]][$key] =
            "<$type for='".$key."' ".$this->attributes($attributes).">".$value."</$type>".PHP_EOL;

        return $this;
    }

    /**
     * @param $key
     * @param $val
     * @return $this
     */
    protected function textarea($key, $val)
    {
        $value = null;
        $value = $val['value'];
        unset($val['value']);

        $extra = [
            'type' => strtolower(__FUNCTION__),
            'value' => $value,
        ];

        return $this->composeElement($key, $val, $extra);
    }

    /**
     * @param $key
     * @param $params
     * @return $this
     */
    protected function select($key, $params)
    {
        $select = $selectValue = '';
        $options = $params['options'];
        unset($params['options']);

        $selected = null;
        if (isset($params['selected'])) {
            $selected = $params['selected'];
            unset($params['selected']);
        }

        $select .= "<".strtolower(__FUNCTION__)." name='".$key."' ".$this->attributes($params).">".PHP_EOL;

        /*
         | Build select box options and return as string
         |
         */
        $select .= $this->getSelectOptions($options, $selected);
        $select .= '</'.strtolower(__FUNCTION__).'>'.PHP_EOL;

        $this->elements[static::$formHolder[static::$formName]][$key] = $select;

        return $this;
    }

    /**
     * @param $options
     * @param $selected
     * @return string
     */
    private function getSelectOptions($options, $selected)
    {
        $select = '';

        foreach ($options as $key => $value) {

            $isSelected = ($selected == $key) ? 'selected="selected"' : '';
            $select .= "<option $isSelected value='".$key."'>".$value."</option>".PHP_EOL;
        }

        return $select;
    }

    /**
     * @param $attributes
     * @return string
     */
    protected function attributes($attributes)
    {
        $elementStr = "";

        foreach ($attributes as $key => $value) {
            $elementStr .= "{$key}='{$value}' ";
        }

        return $elementStr;
    }
}