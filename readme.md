# Kirby API-Pages 

[![Kirby 5](https://flat.badgen.net/badge/Kirby/5?color=ECC748)](https://getkirby.com)
![PHP 8.2](https://flat.badgen.net/badge/PHP/8.2?color=4E5B93&icon=php&label)
![Release](https://flat.badgen.net/packagist/v/bnomei/kirby-api-pages?color=ae81ff&icon=github&label)
![Downloads](https://flat.badgen.net/packagist/dt/bnomei/kirby-api-pages?color=272822&icon=github&label)
[![Coverage](https://flat.badgen.net/codeclimate/coverage/bnomei/kirby-api-pages?icon=codeclimate&label)](https://codeclimate.com/github/bnomei/kirby-api-pages)
[![Maintainability](https://flat.badgen.net/codeclimate/maintainability/bnomei/kirby-api-pages?icon=codeclimate&label)](https://codeclimate.com/github/bnomei/kirby-api-pages/issues)
[![Discord](https://flat.badgen.net/badge/discord/bnomei?color=7289da&icon=discord&label)](https://discordapp.com/users/bnomei)
[![Buymecoffee](https://flat.badgen.net/badge/icon/donate?icon=buymeacoffee&color=FF813F&label)](https://www.buymeacoffee.com/bnomei)

Virtual Pages from APIs

## Installation

- unzip [master.zip](https://github.com/bnomei/kirby-api-pages/archive/master.zip) as folder `site/plugins/kirby-api-pages` or
- `git submodule add https://github.com/bnomei/kirby-api-pages.git site/plugins/kirby-api-pages` or
- `composer require bnomei/kirby-api-pages`

## Usage

You can find these examples in the tests of this repository.

### Records definition via Blueprint

**site/models/cats.php**
```php
class CatsPage extends \Bnomei\APIRecordsPage {}
```

**site/blueprints/cat.yml**
```yml
title: Cat
fields:
    country:
        type: text
    origin:
        type: text
    coat:
        type: text
    pattern:
        type: text
```

**site/blueprints/cats.yml**
```yml
title: Cats

records:
  url: https://catfact.ninja/breeds
  query: data.sortBy("coat", "desc")
  template: cat
  # model: cat
  # expire: 60
  map:
    title: breed
    # omit or use * to select all
    # content: *
    # select a few by path
    content:
      country: country
      origin: origin
      coat: coat
      pattern: pattern

sections:
  catfacts:
    label: Virtual Pages from CatFacts API
    type: pages
    template: cat
```

### Records definition via Config

**site/blueprints/rickandmorty.yml**
**site/blueprints/alien.yml**
**site/blueprints/human.yml**

**site/models/rickandmorty.php**
```php
class RickandmortyPage extends \Bnomei\APIRecordsPage {}
```

**site/config/config.php**
```php
<?php

return [
    'bnomei.api-pages.records' => [
        'rickandmorty' => [ // site/models/rickandmorty.php & site/blueprints/pages/rickandmorty.yml
            'url' => 'https://rickandmortyapi.com/graphql', // string or closure
            'params' => [
                'headers' => function (\Bnomei\APIRecords $records) {
                    // you could add Basic/Bearer Auth within this closure if you needed
                    // or retrieve environment variable with `env()` and use them here
                    return [
                        'Content-Type: application/json',
                    ];
                },
                'method' => 'POST', // defaults to GET else provide a string or closure
                'data' => json_encode(['query' => '{ characters() { results { name status species }}}']), // string or closure
            ],
            'query' => 'data.characters.results', // {"data: [...]}
            'map' => [
                // kirby <=> json
                'title' => 'name',
                'uuid' => fn ($i) => md5($i['name']),
                'template' => fn ($i) => strtolower($i['species']), // site/blueprints/pages/alien.yml || human.yml
                'content' => [
                    'species' => 'species',
                    'hstatus' => 'status', // status is reserved by kirby
                ],
            ],
        ],
    ],
    // other options ...
];
```

### Records definition via Page Model

**site/blueprints/secret.yml**
**site/blueprints/secrets.yml**

**site/models/secrets.php**
```php
class SecretsPage extends \Bnomei\APIRecordsPage {
    public function recordsConfig(): array
    {
        return [
            'url' => 'https://example.api/secrets', // does not exist
            'params' => [
                'headers' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer MY_BEARER_TOKEN',
                ],
                'method' => 'POST',
                'data' => json_encode([
                    'query' => $this->myquery()->value(),
                ]),
            ],
            'query' => 'data.whispers',
            'template' => 'secret',
            'map' => [
                // kirby <=> json
                'title' => 'item.name',
                'content' => [
                    'description' => 'item.desc',
                    'uuid' => 'id',
                ],
            ],
        ];
    }
}
```

## Settings

| bnomei.api-pages. | Default       | Description                                                                   |
|-------------------|---------------|-------------------------------------------------------------------------------|
| expire            | `60`          | Cache expire duration in minutes                                              |
| exception         | `fn($remote)` | Exception handler for non 2xx status codes                                    |
| records           | `[]`          | Custom config arrays for your virtual Pages (alternative to Blueprint config) |

## Disclaimer

This plugin is provided "as is" with no guarantee. You can use it at your own risk and always test it before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby-api-pages/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.
