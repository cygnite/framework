<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Database\Cyrus;

use Cygnite\Validation\Validator;

/**
 * trait ValidationTrait
 *
 * @package Cygnite\Database\Cyrus
 */
trait ValidationTrait
{
    public $errors  = [];

    public $inputs = [];

    public $validation;

    /**
     * @return $this
     */
    public function validator()
    {
        $this->validation = Validator::create($this->inputs);

        return $this;
    }

    /**
     * @return $this
     */
    public function addRule()
    {
        foreach ($this->rules as $field => $rule) {
            $this->validation->{__FUNCTION__}($field, $rule);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function run()
    {
        return ($this->validation->{__FUNCTION__}()) ? true : false;
    }

    /**
     * @param $inputs
     * @return bool
     * @throws \RuntimeException
     */
    public function validate($inputs)
    {
        if(empty($this->rules)) {
            return false;
        }

        if (empty($inputs)) {
            throw new \RuntimeException("Empty array passed to validate method");
        }

        $this->inputs = $inputs;
        $this->validator()->addRule();

        if (!$this->run()) {
            $this->setErrors($this->validation->getErrors());

            return false;
        }

        return true;
    }

    /**
     * Set all validation errors into array
     *
     * @param $errors
     */
    private function setErrors($errors)
    {
        $this->errors = $errors;
    }

    /**
     * We will return validation errors if any
     *
     * @return array
     */
    public function validationErrors()
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }
}
