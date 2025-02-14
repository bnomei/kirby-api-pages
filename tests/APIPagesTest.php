<?php

use Bnomei\APIRecord;

it('can fluently create a record', function () {
    $record = new APIRecord;
    expect($record)->toBeInstanceOf(APIRecord::class);

    $record
        ->title('Some Title')
        ->content(['hello' => 'world'])
        ->model('x')
        ->uuid('123')
        ->template('y')
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
    expect(page('secrets')->children()->count())->not()->toBe(0);

    expect(page('secrets/typo3')->content()->toArray())->toHaveKeys(['title', 'uuid', 'description']);
})->skipOnLinux();
