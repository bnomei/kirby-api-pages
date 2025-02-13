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
        'content' => ['hello' => 'world'],
        'model' => 'x',
        'num' => 1,
        'slug' => 'some-title',
        'template' => 'y',
        'title' => 'Some Title',
        'uuid' => '123',
    ]);
});

it('can map data to a record', function () {
    $record = new APIRecord(
        data: ['label' => 'Some Title', 'nested' => ['data' => ['hello' => 'world']]],
        map: ['title' => 'label', 'content' => 'nested.data', 'uuid' => fn ($i) => md5($i['label'])]
    );

    expect($record->toArray())->toEqual([
        'content' => ['hello' => 'world'],
        'model' => 'default',
        'num' => null,
        'slug' => 'some-title',
        'template' => 'default',
        'title' => 'Some Title',
        'uuid' => md5('Some Title'),
    ]);
});
