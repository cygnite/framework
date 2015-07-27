<?php
use Cygnite\Validation\Validator;
use Cygnite\Helpers\Inflector;
use Cygnite\Common\Input\Input;
use Mockery as m;

class ValidatorTest extends PHPUnit_Framework_TestCase
{
    public function testValidatorInstance()
    {
        $input = Input::make();

        $v = Validator::create($input->post(), function ($v)
        {
            return $v;
        });

        $this->assertInstanceOf('Cygnite\Validation\Validator', $v);
        $this->assertEquals($v, Validator::create($input->post()));
    }

    /**
    * @expectedException \Cygnite\Validation\Exception\ValidatorException
    *
    */
    public function testInputExceptionThrownOnIncorrectParameterPassed()
    {
        $input = Input::make();

        $v = Validator::create($input->post(), function ($v)
        {
            $v->addRule('foo', 'required|min:3|max:5')
              ->addRule('bar', 'required|is_int')
              ->addRule('something', 'phone|is_string')
              ->addRule('baz', 'valid_email');

            return $v;
        });

        $this->assertFalse($v->run());        
    }

    
    public function testWorkingWithArrayOfInputs()
    {
        $input = ['foo' => 'bar', 'bar' => 'foobar','baz' => 'barbaz'];

        $v = Validator::create($input, function ($v)
        {
            $v->addRule('foo', 'required|min:3|max:5')
              ->addRule('bar', 'is_int|phone')
              ->addRule('baz', 'valid_email');

            return $v;
        });

        $this->assertFalse($v->run());
    }

    public function testAfterValidationCallback()
    {
        $input = ['foo' => 'bar', 'bar' => 'foobar','baz' => 'barbaz'];

        $v = Validator::create($input, function ($v)
        {
            $v->addRule('foo', 'required')
              ->addRule('bar', 'is_int')
              ->addRule('baz', 'valid_email');

            return $v;
        });

        $v->after(function ($v)
        {
            $v->setCustomError('foo.error', 'Some Error Occured!!');
            $_SERVER['validator.after.event'] = true;
        });

        $this->assertFalse($v->run());
        $this->assertEquals(trim('Some Error Occured!!'), trim($v->getErrors('foo')));
        $this->assertTrue($_SERVER['validator.after.event']);
        unset($_SERVER['validator.after.event']);
    }

    public function testValidationPassedNonEmptyErrors()
    {
        $input = ['foo' => 'bar', 'bar' => 1025770,'baz' => 'barbaz@gmail.com'];

        $v = Validator::create($input, function ($v)
        {
            $v->addRule('foo', 'required')
              ->addRule('bar', 'is_int')
              ->addRule('baz', 'valid_email');

            return $v;
        });

        $v->run();

        $this->assertEmpty($v->getErrors());
    }

    public function testValidationFailedHasErrors()
    {
        $input = ['foo' => 'bar', 'bar' => 'Hi Bar!','foobar' => 323,'baz' => 'barbaz@gmail.com'];

        $v = Validator::create($input, function ($v)
        {
            $v->addRule('foo', 'required:min:3')
              ->addRule('bar', 'phone|is_int')
              ->addRule('foobar', 'is_string|min:5')
              ->addRule('baz', 'valid_email');

            return $v;
        });

        $v->run();
        $error = $v->getErrors();

        $this->assertEmpty(!$error);
        $this->assertEquals('Bar should be valid phone number and must be valid integer', $error['bar.error']);
        $this->assertEquals('Foobar should be valid string and Foobar should be minimum 5 characters.', $error['foobar.error']);
    }

    public function testWorkingWithArrayOfRulesFailure()
    {
        $input = ['foo' => 'bar', 'bar' => 1025770,'baz' => '12.4.11', 
                  'foobar' => 'foo!#$bar', 'foobaz' => 38025770, 'barbaz' => '12345',
                  'barbar' => 'foo!#$bar_'
                ];
                
        $rules = [
                'foo' => 'required|min:3|max:5',
                'bar' => 'is_string',
                'baz' => 'is_ip',
                'foobar' => 'is_alpha_numeric',
                'foobaz' => 'phone',
                'barbaz' => 'valid_date',
                'barbar' => 'is_alpha_num_with_under_score'
        ];

        $v = Validator::create($input);
        $v->addRules($rules);
        $v->run();

        $errors = $v->getErrors();

        $this->assertEmpty(!$errors);
        $this->assertEquals('Foo should be maximum 5 characters.', $errors['foo.error']);
        $this->assertEquals('Bar should be valid string', $errors['bar.error']);
        $this->assertEquals('Baz is not valid ip', $errors['baz.error']);
        $this->assertEquals('Foobar should be alpha numeric.', $errors['foobar.error']);
        $this->assertEquals('Foobaz should be valid phone number.', $errors['foobaz.error']);
        $this->assertEquals('Barbaz is not valid date.', $errors['barbaz.error']);
        $this->assertEquals('Barbar must be alpha numeric with underscore/dash', $errors['barbar.error']);
    }

    public function testWorkingWithArrayOfRulesPassed()
    {
        $input = [
                  'foo' => 'foobar1', 'bar' => 'Hello World!','baz' => '127.0.0.1', 
                  'foobar' => 'AbCd1zyZ9', 'foobaz' => 9538025770, 'barbaz' => '2015-07-25'
                ];

        $rules = [
                'foo' => 'required|min:3|max:5',
                'bar' => 'is_string',
                'baz' => 'is_ip',
                'foobar' => 'is_alpha_numeric',
                'foobaz' => 'phone',
                'barbaz' => 'valid_date'
        ];

        $v = Validator::create($input);
        $v->addRules($rules);
        $v->run();

        $errors = $v->getErrors();
        $this->assertTrue($v->run());
        $this->assertEmpty($errors);
    }
}