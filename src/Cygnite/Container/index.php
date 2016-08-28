<?php

$container = new \Cygnite\Container\Container();
//$obj = new Service;

$container['a'] = 'Store A';
$container['b'] = 'Store B';
$container->c = 'Store C';
//        $container->d = function ($name) {
//             return "Hello World!";
//        };
//
//var_dump($container['a']());exit;
$container['d'] = function ($c) use ($service) {
    return new Tes($c['a']);
};

//var_dump($container['d']());exit;
//$container['e'] =
$container['d'] = $container->extend('d', function ($tes, $c) {
    $tes->name = 'Sanjay';

    return $tes;
});
var_dump($container['d']());

echo '<pre>';
var_dump($container['d']);
//var_dump($container['e']);

$obj['dynamicFields'] = function ($c) {
    return new Users();
};

//var_dump($obj['dynamicFields']);

$container['dynamicFields'] = $container->extend('dynamicFields', function ($user, $c) {
    $user->form = 'User Form';
    $user->setUser($c['a']);

    return $user;
});

$container['dynamicFields']()->start('Ami');
var_dump($container['dynamicFields']());
