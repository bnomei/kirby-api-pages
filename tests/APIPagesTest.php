<?php

use Bnomei\APIRecord;
use Bnomei\APIRecords;

it('can fluently create a record', function () {
    $record = new APIRecord;
    expect($record)->toBeInstanceOf(APIRecord::class);

    $record
        ->title('Some Title')
        ->content(['hello' => 'world'])
        ->uuid('123')
        ->template('y')
        ->model('x')
        ->num(1);

    expect($record->toArray())->toEqual([
        'content' => [
            'hello' => 'world',
            'title' => 'Some Title',
            'uuid' => '123',
        ],
        'id' => 'some-title',
        'model' => 'x',
        'num' => 1,
        'slug' => 'some-title',
        'template' => 'y',
    ]);
});

it('can map data to a record', function () {
    $record = new APIRecord(
        data: [
            'label' => 'Some Title',
            'nested' => ['data' => ['hello' => 'world']],
        ],
        map: [
            'title' => 'label',
            'content' => 'nested.data',
            'uuid' => fn ($i) => md5($i['label']),
        ]
    );

    expect($record->toArray())->toEqual([
        'content' => [
            'hello' => 'world',
            'title' => 'Some Title',
            'uuid' => md5('Some Title'),
        ],
        'id' => 'some-title',
        'model' => 'default',
        'num' => null,
        'slug' => 'some-title',
        'template' => 'default',
    ]);
});

it('does not read or write records cache when cache config is disabled', function () {
    $cacheKey = md5('https://example.com/api'.json_encode([]));
    $cached = ['items' => [['title' => 'Cached']]];
    $fresh = ['items' => [['title' => 'Fresh']]];

    kirby()->cache('bnomei.api-pages')->set($cacheKey, $cached, 60);

    $records = new class(false, $fresh) extends APIRecords
    {
        public int $fetches = 0;

        public function __construct(
            private bool $cacheEnabled,
            private array $response,
        ) {
            parent::__construct();
        }

        public function config(string $key, mixed $default = null, bool $resolveClosures = false): mixed
        {
            return match ($key) {
                'url' => 'https://example.com/api',
                'params' => [],
                'query' => 'items',
                'cache' => $this->cacheEnabled,
                'expire' => 60,
                default => $default,
            };
        }

        public function fetch(?string $after = null): array
        {
            $this->fetches++;

            return $this->response;
        }
    };

    expect($records->fetchPaginated())->toBe($fresh)
        ->and($records->fetches)->toBe(1)
        ->and(kirby()->cache('bnomei.api-pages')->get($cacheKey))->toBe($cached);

    kirby()->cache('bnomei.api-pages')->remove($cacheKey);
});

it('writes records cache when cache config is enabled', function () {
    $cacheKey = md5('https://example.com/api'.json_encode([]));
    $fresh = ['items' => [['title' => 'Fresh']]];

    kirby()->cache('bnomei.api-pages')->remove($cacheKey);

    $records = new class(true, $fresh) extends APIRecords
    {
        public function __construct(
            private bool $cacheEnabled,
            private array $response,
        ) {
            parent::__construct();
        }

        public function config(string $key, mixed $default = null, bool $resolveClosures = false): mixed
        {
            return match ($key) {
                'url' => 'https://example.com/api',
                'params' => [],
                'query' => 'items',
                'cache' => $this->cacheEnabled,
                'expire' => 60,
                default => $default,
            };
        }

        public function fetch(?string $after = null): array
        {
            return $this->response;
        }
    };

    expect($records->fetchPaginated())->toBe($fresh)
        ->and(kirby()->cache('bnomei.api-pages')->get($cacheKey))->toBe($fresh);

    kirby()->cache('bnomei.api-pages')->remove($cacheKey);
});

it('can create virtual pages for catfacts (rest api from blueprint config)', function () {
    expect(page('cats')->children()->count())->not()->toBe(0);

    $content = page('cats/bombay')->content()->toArray();
    unset($content['uuid']); // is dynamic for this example
    expect($content)->toBe([
        'title' => 'Bombay',
        'country' => 'developed in the United States (founding stock from Asia)',
        'origin' => 'Crossbred',
        'coat' => 'Short',
        'pattern' => 'Solid',
    ]);
});

it('can create virtual pages for rickandmorty (tokenless graphql from PHP options)', function () {
    expect(page('rickandmorty')->children()->count())->not()->toBe(0);

    $content = page('rickandmorty/albert-einstein')->content()->toArray();
    expect($content)->toBe([
        'title' => 'Albert Einstein',
        'uuid' => md5('Albert Einstein'),
        'species' => 'Human',
        'hstatus' => 'Dead',
    ]);
});

it('can create virtual pages for a secret api (basic auth graphql from PHP model)', function () {
    $secrets = page('secrets');

    if ($secrets === null) {
        $this->markTestSkipped('Secret API fixture is not available.');
    }

    expect($secrets->children()->count())->not()->toBe(0);

    expect(page('secrets/typo3')->content()->toArray())->toHaveKeys(['title', 'uuid', 'description']);
});
