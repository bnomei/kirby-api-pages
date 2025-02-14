<?php

namespace Bnomei;

use Kirby\Cms\Nest;
use Kirby\Cms\Page;
use Kirby\Content\Field;
use Kirby\Http\Remote;
use Kirby\Query\Query;
use Kirby\Toolkit\A;

class APIRecords
{
    private ?array $records;

    private ?string $endpointUrl;

    private ?array $endpointParams;

    private ?string $recordsDataQuery;

    private ?array $recordsDataMap;

    private ?int $recordsCacheExpire;

    private ?string $recordTemplate;

    private ?string $recordModel;

    private string $cacheKey;

    public function __construct(protected ?APIRecordsPage $page = null)
    {
        $this->records = null;

        $this->endpointUrl = $this->config('url', resolveClosures: true);
        $this->endpointParams = $this->config('params', [], true);
        $this->recordsDataQuery = $this->config('query', resolveClosures: true);
        $this->recordsDataMap = $this->config('map'); // closure resolving here would break the mapping by closure
        $this->recordsCacheExpire = $this->config('expire', intval(option('bnomei.api-pages.expire')), true); // @phpstan-ignore-line
        $this->recordTemplate = $this->config('template');
        $this->recordModel = $this->config('model');
        $this->cacheKey = md5(implode('', [$this->endpointUrl, json_encode($this->endpointParams)]));
    }

    public function page(): ?Page
    {
        return $this->page;
    }

    public function config(string $key, mixed $default = null, bool $resolveClosures = false): mixed
    {
        $result = null;

        // try from model itself
        $config = $this->page?->recordsConfig();
        if (! empty($config)) {
            if (is_array($config)) {
                $result = A::get($config, $key);
            } elseif ($config instanceof Field) {
                $result = $config->value();
                $json = is_string($result) ? json_decode($result, true) : false;
                if (is_array($json)) {
                    $result = $json;
                }
            }
        }

        // try a config value for page template name
        if (! $result && $template = $this->page?->intendedTemplate()->name()) {
            $result = option("bnomei.api-pages.records.{$template}.{$key}", null);
        }

        // try blueprint
        if (! $result && $fromBlueprint = A::get($this->page?->blueprint()->toArray() ?? [], 'records.'.$key)) {
            $result = $fromBlueprint;
        }

        // resolve closures
        if ($resolveClosures && is_array($result)) {
            array_walk($result, function (&$value) {
                if ($value instanceof \Closure) {
                    $value = $value($this);
                }
            });
        }

        return $result ?? $default;
    }

    /**
     * @return array<int, APIRecord>
     */
    public function toArray(): array
    {
        if ($this->records) {
            return $this->records;
        }

        $map = $this->recordsDataMap;
        $data = $this->fetch();

        // handle the data like Kirby's OptionApi does to allow for the entry query with sorting etc.
        $data = Nest::create($data);
        $result = Query::factory($this->recordsDataQuery)->resolve($data);
        $data = $result?->toArray() ?? []; // @phpstan-ignore-line

        $records = array_map(function (array $data) use ($map) {
            // create the record object which resolves data with the map
            $record = new APIRecord(
                data: $data,
                map: $map ?? [],
                parent: $this->page?->id(),
            );

            // mapping might not have set template and model yet, so apply from config.
            // null values will not overwrite the values from the mapping step before.
            $record->template($this->recordTemplate)
                ->model($this->recordModel);

            // lastly, if there is no map at all then add ALL data as content
            if (empty($map) || in_array(A::get($map, 'content'), [null, '*'])) {
                $record->content($data);
            }

            return $record;
        }, $data);

        return $this->records = $records;
    }

    public function fetch(): array
    {
        // get cache if it exists
        $cache = kirby()->cache('bnomei.api-pages')->get($this->cacheKey);
        if ($cache) {
            return $cache;
        }

        // fetch from remote
        $params = $this->endpointParams;
        $expire = $this->recordsCacheExpire;
        $method = strtolower($params['method'] ?? 'GET');
        $remote = Remote::$method($this->endpointUrl, $params);

        if ($remote->code() >= 200 && $remote->code() <= 300) {
            $json = $remote->json() ?? [];
            if (! is_null($expire) && $expire >= 0) {
                kirby()->cache('bnomei.api-pages')->set($this->cacheKey, $json, $expire);
            }

            return $json;
        } else {
            $ex = option('bnomei.api-pages.exception');
            if ($ex instanceof \Closure) {
                $ex($remote);
            }
        }

        return [];
    }

    public function remove(): bool
    {
        return kirby()->cache('bnomei.api-pages')->remove($this->cacheKey);
    }

    public static function flush(): bool
    {
        return kirby()->cache('bnomei.api-pages')->flush();
    }
}
