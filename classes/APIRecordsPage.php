<?php

namespace Bnomei;

use Kirby\Cms\Page;
use Kirby\Cms\Pages;

class APIRecordsPage extends Page
{
    protected function records(): APIRecords
    {
        return new APIRecords($this);
    }

    public function children(): Pages
    {
        if ($this->children instanceof Pages) {
            return $this->children;
        }

        $pages = [];

        foreach ($this->records()->toArray() as $record) {
            $page = $record->toArray();

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
}
