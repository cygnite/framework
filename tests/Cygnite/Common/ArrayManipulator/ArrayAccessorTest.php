<?php

use PHPUnit\Framework\TestCase;
use Cygnite\Common\ArrayManipulator\ArrayAccessor;

class ArrayAccessorTest extends TestCase
{
    public function testMakeInstance()
    {
        $array = ArrayAccessor::make([]);
        $this->assertInstanceOf('Cygnite\Common\ArrayManipulator\ArrayAccessor', $array);
    }

    public function testAccessArrayKeyAsString()
    {
        $array = [
            'profile' => [
                'experience'  => [
                    'field'         => 'Web Development',
                    'technology'    => 'PHP',
                ],
            ],
        ];

        $accessor = ArrayAccessor::make($array);
        $this->assertNotNull($accessor->toString('profile.experience.field'));
        $this->assertEquals('Web Development', $accessor->toString('profile.experience.field'));
    }

    public function testAccessingArrayWhenDotExistsInIndex()
    {
        $array = [
            'profile' => [
                'experience'  => [
                    'technology.version'  => 'Welcome to PHP v5.4',
                ],
            ],
        ];

        $accessor = ArrayAccessor::make($array);
        $this->assertNotNull($accessor->toString('profile.experience.technology_version'));
        $this->assertEquals('Welcome to PHP v5.4', $accessor->toString('profile.experience.technology_version'));
        $this->assertEquals('Welcome to PHP v5.4', $accessor->toString('profile.experience.technology-version'));
    }

    public function testReturnDefaultValueWhenArrayKeyNotExists()
    {
        $array = [
            'profile' => [
                'experience'  => '4 Years',
            ],
        ];

        $accessor = ArrayAccessor::make($array);
        $this->assertEquals('Application Development', $accessor->toString('profile.experience.area', 'Application Development'));
    }

    public function testResolveClosureCallback()
    {
        $array = [
            'profile' => [
                'author'  => 'Sanjoy Dey',
            ],
        ];
        $output = ArrayAccessor::make($array, function ($a) {
            return $a->toString('profile.author');
        });

        $this->assertEquals('Sanjoy Dey', $output);
    }

    public function testGetOutputAsJson()
    {
        $array = [
            'foo' => [
                'bar'  => 'Foo Bar',
            ],
        ];
        $output = ArrayAccessor::make($array, function ($a) {
            return $a->toJson();
        });

        $this->assertJson($output);
    }

    public function testHasKey()
    {
        $array = [
            'foo' => [
                'bar'  => 'Foo Bar',
            ],
        ];

        $output = ArrayAccessor::make($array, function ($a) {
            return $a->has('bar');
        });

        $this->assertTrue($output);
    }
}
