<?php

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('bnomei/api-pages', [
    'options' => [
        'cache' => true,
    ],
]);
