<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Validation;

/**
 * trait ValidationTrait.
 */
trait ValidationTrait
{
    public $errors = [];

    public $inputs = [];

    public $validation;

    /**
     * @return $this
     */
    public function validator($inputs)
    {
        $this->inputs = $inputs;
        $this->validation = Validator::create($inputs);

        return $this;
    }

    /**
     * Get the validator instance.
     *
     * @return mixed
     */
    public function getValidator()
    {
        return $this->validation;
    }

    /**
     * Add rules to validator.
     *
     * @return $this
     */
    public function addRule()
    {
        foreach ($this->rules as $field => $rule) {
            $this->validation->addRule($field, $rule);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function run()
    {
        return ($this->validation->run()) ? true : false;
    }

    /**
     * We will validate for and return boolean
     * value.
     *
     * @param $inputs
     *
     * @throws \RuntimeException
     *
     * @return bool
     */
    public function validate($inputs)
    {
        if (empty($this->rules)) {
            throw new \RuntimeException('You must set rules for validator.');
            return false;
        }

        if (empty($inputs)) {
            throw new \RuntimeException('Empty array passed to validate method');
        }

        $this->validator($inputs)->addRule();

        if (!$this->run()) {
            $this->setErrors($this->validation->getErrors());

            return false;
        }

        return true;
    }

    /**
     * Set all validation errors into array.
     *
     * @param $errors
     */
    private function setErrors($errors)
    {
        $this->errors = $errors;
    }

    /**
     * We will return validation errors if any.
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
