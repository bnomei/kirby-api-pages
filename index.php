<?php

use Bnomei\APIRecordsPage;
use Kirby\Cms\Page;

@include_once __DIR__.'/vendor/autoload.php';

Kirby::plugin('bnomei/api-pages', [
    'options' => [
        'cache' => true,
        'expire' => 60, // in minutes
        // copy this to your config as bnomei.api-pages.exception
        'exception' => function (\Kirby\Http\Remote $remote) {
            // throw new \Exception($remote->url().' => '.$remote->code());
        },
        'records' => [
            // register custom record configs as an alternative to the blueprint
            // see example in tests/config/config.php
        ],
    ],
    'hooks' => [
        'page.update:before' => function (Page $page, array $values, array $strings) {
            // changes on the page remove the cache of the query in case the page
            // forwards some of its fields to the API call as params
            if ($page instanceof APIRecordsPage) {
                $page->records()->remove();
            }
        },
    ],
    'commands' => [
        'apipages:flush' => [
            'description' => 'Flush API-Pages Cache',
            'args' => [],
            'command' => static function ($cli): void {
                $cli->out('🚽 Flushing API-Pages Cache...');
                \Bnomei\APIRecords::flush(); // flushes ALL caches of ALL known APIRecordPage instances
                $cli->success('✅ Done.');

                if (function_exists('janitor')) {
                    janitor()->data($cli->arg('command'), [
                        'status' => 200,
                        'message' => 'API-Pages flushed.',
                    ]);
                }
            },
        ],
    ],
]);
