<?php
namespace Cygnite\Tests\Container;

class ContainerDependency
{
    public function getInjector()
    {
        return new \Cygnite\Container\Injector();
    }

    public function getControllerNamespace()
    {
        return "\\";
    }

    public function getDefinitiions()
    {
        return [
            'property.definition' =>  [
                'TestClassDependencies' => [
                    'api' => \TestMethodResolve::class,
                ]
            ],
            'register.alias' => [
                'TestImplementInterface' => \TestClassDependenciesImplement::class
            ]
        ];
    }
}
