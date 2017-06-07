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

if (!defined('CF_SYSTEM')) {
    exit('No External script access allowed');
}
use Cygnite\Helpers\Inflector;
use Cygnite\Validation\ValidatorInterface;

/**
 * Class Elements.
 *
 * @package Cygnite\FormBuilder\Html
 */
class Elements
{
    protected $elements = [];

    protected $openTagHolder;

    /**
     * Create html input element.
     *
     * @param $key
     * @param $val
     *
     * @return $this
     */
    protected function input($key, $val)
    {
        $extra = [
            'type' => strtolower(__FUNCTION__),
        ];

        return $this->composeElement($key, $val, $extra, true);
    }

    /**
     * Create a button.
     *
     * @param $key
     * @param $val
     * @return Elements
     */
    protected function button($key, $val)
    {
        $extra = [
            'type' => strtolower(__FUNCTION__),
        ];

        return $this->composeElement($key, $val, $extra);
    }

    /**
     * Create a custom element.
     * <code>
     * ->addElement('custom', 'dl', ['name' => '<span style="color:red;">Custom Tag</span>',)
     * </code>.
     *
     * @param $key
     * @param $val
     *
     * @return $this
     */
    protected function custom($key, $val)
    {
        $extra = [
            'value' => (isset($val['name'])) ? $val['name'] : $key,
        ];

        return $this->composeElement($key, $val, $extra);
    }

    /**
     * <code>
     * ->addElement('openTag', 'div_1', ['style' => 'height:40px;'])
     * </code>.
     *
     * @param $key
     * @param $val
     *
     * @return $this
     */
    protected function openTag($key, $val)
    {
        $exp = explode('_', $key);
        $this->openTagHolder = $exp[1];

        $extra = [
            'type' => $exp[0],
        ];

        return $this->composeElement($key, $val, $extra, true);
    }

    /**
     * <code>
     * ->addElement('closeTag', 'div_1')
     * </code>.
     *
     * @param $key
     *
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
     * Create a label element form form.
     *
     * @param $key
     * @param $val
     *
     * @return $this
     */
    protected function label($key, $val)
    {
        $extra = [
            'type' => strtolower(__FUNCTION__),
        ];

        return $this->composeElement($key, $val, $extra);
    }

    /**
     * Create a html element.
     *
     * @param       $key
     * @param       $attributes
     * @param array $extra
     * @param bool  $hasCloseTag
     *
     * @return $this
     */
    private function composeElement($key, $attributes, $extra = [], $hasCloseTag = false)
    {
        $value = (isset($extra['value'])) ? $extra['value'] : $key;
        $type = (isset($extra['type'])) ? $extra['type'] : $key;
        list($elClass, $attributes) = $this->highlightErrorElement($key, $attributes);

        $val = '';
        if ($hasCloseTag) {
            if (is_object($this->entity)) {
                if (method_exists($this->entity, 'get'.Inflector::camelize($key))) {
                    $val = $this->entity->{'get'.Inflector::camelize($key)}();
                } elseif (property_exists($this->entity, $key)) {
                    $property = (new \ReflectionClass($this->entity))->getProperty($key);
                    if ($property->isPublic()) {
                        $val = $property->getValue();
                    }
                } else {
                    $val = $this->entity->{$key};
                }
            }

            $val = (!isset($attributes['value'])) ? (!empty($val)) ? 'value="'.$val.'"' : ''  : '';

            $this->elements[static::$formHolder[static::$formName]][$key] =
                "<$type name='".$key."' $val ".$this->attributes($attributes). ' '.$elClass.' />'.PHP_EOL;

            return $this;
        }

        $this->elements[static::$formHolder[static::$formName]][$key] =
            "<$type for='".$key."' ".$this->attributes($attributes). ' '.$elClass.' >'.$value."</$type>".PHP_EOL;


        return $this;
    }

    /**
     * Create textarea element.
     *
     * @param $key
     * @param $val
     *
     * @return $this
     */
    protected function textarea($key, $val)
    {
        $value = null;
        $value = isset($val['value']) ? $val['value'] : '' ;
        unset($val['value']);

        $extra = ['type'  => 'textarea', 'value' => $value];

        return $this->composeElement($key, $val, $extra);
    }

    /**
     * Create a select box.
     *
     * @param $key
     * @param $params
     *
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

        list($elClass, $params) = $this->highlightErrorElement($key, $params);

        $select .= '<select'." name='".$key."' ".$this->attributes($params).' '.$elClass.'>'.PHP_EOL;

        /*
         | Build select box options and return as string
         |
         */
        $select .= $this->getSelectOptions($options, $selected);
        $select .= '</select>'.PHP_EOL;

        $this->elements[static::$formHolder[static::$formName]][$key] = $select;

        return $this;
    }

    /**
     * Returns select box option elements.
     *
     * @param $options
     * @param $selected
     * @return string
     */
    private function getSelectOptions($options, $selected)
    {
        $select = '';

        foreach ($options as $key => $value) {
            $isSelected = ($selected == $key) ? 'selected="selected"' : '';
            $select .= "<option $isSelected value='".$key."'>".$value.'</option>'.PHP_EOL;
        }

        return $select;
    }

    /**
     * Create date time local box.
     *
     * @param $key
     * @param $attributes
     * @return Elements
     */
    protected function dateTimeLocal($key, $attributes)
    {
        if (isset($attributes['value']) && $attributes['value'] instanceof \DateTime) {
            $attributes['value'] = $attributes['value']->format('Y-m-d H:m:s');
        }

        return $this->composeElement($key, $attributes, ['type' => 'input'], true);
    }

    /**
     * Returns html element attributes as string.
     *
     * @param $attributes
     * @return string
     */
    protected function attributes($attributes)
    {
        $elementStr = '';

        foreach ($attributes as $key => $value) {
            $elementStr .= ($key !== 0) ? "{$key}='{$value}' " : $value;
        }

        return $elementStr;
    }

    /**
     * Select box options for all 12 months.
     *
     * @param $name
     * @return Elements
     */
    public function selectMonth($name)
    {
        $options = [
            "1" => "January",
            "2" => "February",
            "3" => "March",
            "4" => "April",
            "5" => "May",
            "6" => "June",
            "7" => "July",
            "8" => "August",
            "9" => "September",
            "10" => "October",
            "11" => "November",
            "12" => "December",
        ];

        return $this->select($name, $options);
    }

    /**
     * Highlight element which is failed validation.
     *
     * @param $key
     * @param $attributes
     * @return array
     */
    protected function highlightErrorElement($key, $attributes)
    {
        $elClass = '';
        if (
            $this->validator instanceof ValidatorInterface &&
            $this->validator->hasError($key)
        ) {
            preg_match_all("#class='([^\s]+)'#", $this->attributes($attributes), $matches);
            $elClass = "class='".implode(' ', array_merge([$this->errorInputClass], $matches[1]))."'";
            unset($attributes['class']);
        }

        return [$elClass, $attributes];
    }
}
