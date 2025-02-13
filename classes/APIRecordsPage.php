<?php

namespace Bnomei;

use Kirby\Cms\Nest;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Http\Remote;
use Kirby\Query\Query;
use Kirby\Toolkit\A;

class APIRecordsPage extends Page implements ProvidesAPIRecords
{
    protected function api_records_cacheKey(): string
    {
        $url = $this->recordsUrl();
        $params = $this->recordsUrlRequestParams();

        return md5($url.json_encode($params));
    }

    protected function api_records_fetch(): array
    {
        $url = $this->recordsUrl();
        $params = $this->recordsUrlRequestParams();
        $key = $this->api_records_cacheKey();

        // get cache if it exists
        $cache = $this->kirby()->cache('bnomei.api-pages')->get($key);
        if ($cache) {
            return $cache;
        }

        // fetch from remote
        $expire = $this->recordsCacheExpire();
        $method = strtolower($params['method'] ?? 'GET');
        $remote = Remote::$method($url, $params);

        if ($remote->code() <= 300) {
            $json = $remote->json() ?? [];
            if ($expire >= 0) {
                $this->kirby()->cache('bnomei.api-pages')->set($key, $json, $expire);
            }

            return $json;
        } else {
            // throw new \Exception($url.' => '.$remote->code());
        }

        return [];
    }

    protected function api_records(): array
    {
        $query = $this->recordsDataQuery();
        $map = $this->recordsDataMap();
        $template = $this->recordTemplate();
        $model = $this->recordModel();

        // like Kirby's OptionApi
        $data = $this->api_records_fetch();
        $data = Nest::create($data);
        $data = Query::factory($query)->resolve($data)?->toArray() ?? [];

        return array_map(function (array $data) use ($map, $template, $model) {
            $record = new APIRecord(
                data: $data,
                map: $map
            );
            $record->template($template)
                ->model($model);

            // lastly add all content if there is no map
            if (empty($map) || in_array(A::get($map, 'content'), [null, '*'])) {
                $record->content($data);
            }

            return $record;
        }, $data);
    }

    public function recordsFlush(): bool
    {
        return $this->kirby()->cache('bnomei.api-pages')->remove(
            $this->api_records_cacheKey()
        );
    }

    public static function recordsFlushAll(): bool
    {
        return kirby()->cache('bnomei.api-pages')->flush();
    }

    public function children(): Pages
    {
        if ($this->children instanceof Pages) {
            return $this->children;
        }

        $pages = [];

        /** @var APIRecord $record */
        foreach ($this->api_records() as $record) {
            $page = array_merge_recursive([
                'id' => $this->id().'/'.$record->slug, // unless set explicitly in record
                'content' => [
                    'title' => $record->title,
                ],
            ], $record->toArray());

            if ($this->kirby()->multilang()) {
                $languageCode = $this->kirby()->language()?->code();
                $page['translations'] = [
                    $languageCode => [
                        'code' => $languageCode,
                        'content' => $page['content'],
                    ],
                ];
                unset($page['content']);
            }

            $pages[] = $page;
        }

        usort($pages, function ($a, $b) {
            return $a['num'] <=> $b['num'];
        });

        return $this->children = Pages::factory($pages, $this);
    }

    public function recordsUrl(): string
    {
        return A::get($this->blueprint()->toArray(), 'records.url', '');
    }

    public function recordsUrlRequestParams(): array
    {
        return A::get($this->blueprint()->toArray(), 'records.params', []);
    }

    public function recordsDataQuery(): string
    {
        return A::get($this->blueprint()->toArray(), 'records.query', '');
    }

    public function recordsDataMap(): array
    {
        return A::get($this->blueprint()->toArray(), 'records.map', []);
    }

    public function recordsCacheExpire(): int
    {
        return A::get(
            $this->blueprint()->toArray(),
            'records.expire',
            intval($this->kirby()->option('bnomei.api-pages.expire')) // @phpstan-ignore-line
        );
    }

    public function recordTemplate(): string
    {
        return A::get($this->blueprint()->toArray(), 'records.template', 'default');
    }

    public function recordModel(): string
    {
        return A::get($this->blueprint()->toArray(), 'records.model', 'default');
    }
}
