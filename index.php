<?php

use Bnomei\APIRecordsPage;
use Kirby\Cms\Page;

@include_once __DIR__.'/vendor/autoload.php';

Kirby::plugin('bnomei/api-pages', [
    'options' => [
        'cache' => true,
        'expire' => 60, // in minutes
    ],
    'hooks' => [
        'page.update:before' => function (Page $page, array $values, array $strings) {
            if ($page instanceof APIRecordsPage) {
                $page->recordsFlush();
            }
        },
    ],
]);
